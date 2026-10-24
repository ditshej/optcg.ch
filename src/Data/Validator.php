<?php

declare(strict_types=1);

namespace Optcg\Data;

use JsonSchema\Validator as JsonSchemaValidator;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * Validates data/rooms.yaml and data/links/<room-id>.yaml: structure against
 * the JSON Schemas in data/schema/, plus the cross-file rules from the plan
 * (docs/plan.md section 5) that a JSON Schema cannot express on its own.
 */
final class Validator
{
    /** Icons are scaled to 180x180 px by bin/fetch-icons.php; this leaves headroom for manual uploads. */
    private const MAX_ICON_BYTES = 200 * 1024;

    private const ALLOWED_ICON_EXTENSIONS = ['png', 'svg', 'webp'];

    /** Case-insensitive query parameter name patterns that mark a tracking link. */
    private const TRACKING_PARAM_PATTERNS = [
        '/^utm_.*$/i',
        '/^fbclid$/i',
        '/^gclid$/i',
        '/^gclsrc$/i',
        '/^dclid$/i',
        '/^msclkid$/i',
        '/^mc_eid$/i',
        '/^igshid$/i',
        '/^ref_src$/i',
    ];

    public function __construct(
        private readonly DataPaths $paths,
    ) {
    }

    /**
     * @return ValidationError[]
     */
    public function validate(): array
    {
        $errors = [];

        $roomsRaw = $this->parseYamlFile($this->paths->roomsFile(), $errors);
        $roomIds = null;

        if ($roomsRaw !== null) {
            $errors = [...$errors, ...$this->validateAgainstSchema(
                $roomsRaw,
                $this->paths->roomsSchemaFile(),
                $this->paths->relative($this->paths->roomsFile()),
                fn (array $pointerParts, array $root) => $this->roomLabelFromPointer($pointerParts, $root),
            )];

            $rooms = is_array($roomsRaw['rooms'] ?? null) ? $roomsRaw['rooms'] : [];
            $roomIds = array_values(array_filter(array_map(
                static fn ($room) => is_array($room) ? ($room['id'] ?? null) : null,
                $rooms,
            )));
        }

        $linkFiles = glob($this->paths->linksDir() . '/*.yaml') ?: [];
        sort($linkFiles);

        /** @var array<string, string> $seenLinkIds link id => file it was first seen in */
        $seenLinkIds = [];
        /** @var array<string, string> $seenUrls normalized url => link id it was first seen on */
        $seenUrls = [];

        foreach ($linkFiles as $file) {
            $relFile = $this->paths->relative($file);
            $roomId = basename($file, '.yaml');

            $raw = $this->parseYamlFile($file, $errors);
            if ($raw === null) {
                continue;
            }

            $errors = [...$errors, ...$this->validateAgainstSchema(
                $raw,
                $this->paths->linksSchemaFile(),
                $relFile,
                fn (array $pointerParts, array $root) => $this->linkIdFromPointer($pointerParts, $root),
            )];

            if ($roomIds !== null && !in_array($roomId, $roomIds, true)) {
                $errors[] = new ValidationError(
                    $relFile,
                    null,
                    "The room '{$roomId}' (from the file name) does not exist in data/rooms.yaml.",
                    "Rename the file to an existing room id, or add the room to data/rooms.yaml first.",
                );
            }

            $links = is_array($raw['links'] ?? null) ? $raw['links'] : [];

            foreach ($links as $link) {
                if (!is_array($link)) {
                    continue;
                }

                $linkId = is_string($link['id'] ?? null) ? $link['id'] : null;
                $label = $linkId ?? '(missing id)';

                if (isset($link['url']) && is_string($link['url'])) {
                    $trackingParams = $this->findTrackingParams($link['url']);
                    if ($trackingParams !== []) {
                        $errors[] = new ValidationError(
                            $relFile,
                            $label,
                            "url contains tracking parameter(s): " . implode(', ', $trackingParams) . '.',
                            'Remove the tracking parameter(s) from the url.',
                        );
                    }
                }

                if ($linkId !== null) {
                    if (isset($seenLinkIds[$linkId])) {
                        $errors[] = new ValidationError(
                            $relFile,
                            $linkId,
                            "id '{$linkId}' is already used in {$seenLinkIds[$linkId]}.",
                            'Choose a different, unique id.',
                        );
                    } else {
                        $seenLinkIds[$linkId] = $relFile;
                    }
                }

                if (isset($link['url']) && is_string($link['url'])) {
                    $normalizedUrl = $this->normalizeUrl($link['url']);
                    if (isset($seenUrls[$normalizedUrl])) {
                        $errors[] = new ValidationError(
                            $relFile,
                            $label,
                            "url is already used by link '{$seenUrls[$normalizedUrl]}'.",
                            'Remove the duplicate link, or point it to a different URL.',
                        );
                    } else {
                        $seenUrls[$normalizedUrl] = $label;
                    }
                }

                if (!empty($link['icon']) && is_string($link['icon'])) {
                    $errors = [...$errors, ...$this->validateIcon($link['icon'], $relFile, $label)];
                }
            }
        }

        return $errors;
    }

    /**
     * @param array<string, mixed> $errorsOut
     * @return array<string, mixed>|null
     */
    private function parseYamlFile(string $file, array &$errorsOut): ?array
    {
        $relFile = $this->paths->relative($file);

        if (!is_file($file)) {
            $errorsOut[] = new ValidationError($relFile, null, 'File not found.', 'Add the file.');

            return null;
        }

        try {
            $data = Yaml::parseFile($file);
        } catch (ParseException $e) {
            $errorsOut[] = new ValidationError(
                $relFile,
                null,
                "File is not valid YAML: {$e->getMessage()}",
                'Fix the YAML syntax.',
            );

            return null;
        }

        if (!is_array($data)) {
            $errorsOut[] = new ValidationError($relFile, null, 'File must contain a YAML mapping.', 'Fix the file structure.');

            return null;
        }

        return $data;
    }

    /**
     * @param array<string, mixed> $data
     * @param callable(list<string>, array<string, mixed>): ?string $labelResolver resolves a JSON pointer to a link/room id
     * @return ValidationError[]
     */
    private function validateAgainstSchema(array $data, string $schemaFile, string $relFile, callable $labelResolver): array
    {
        $schema = json_decode((string) file_get_contents($schemaFile));
        $instance = json_decode((string) json_encode($data));

        $validator = new JsonSchemaValidator();
        $validator->validate($instance, $schema);

        if ($validator->isValid()) {
            return [];
        }

        $errors = [];
        foreach ($validator->getErrors() as $error) {
            $pointerParts = array_values(array_filter(explode('/', (string) $error['pointer']), static fn ($p) => $p !== ''));
            $label = $labelResolver($pointerParts, $data);

            $errors[] = new ValidationError(
                $relFile,
                $label,
                "{$error['property']}: {$error['message']}",
                $this->fixHintForConstraint((string) $error['constraint']['name']),
            );
        }

        return $errors;
    }

    private function fixHintForConstraint(string $constraintName): string
    {
        return match ($constraintName) {
            'required' => 'Add the missing field.',
            'enum' => 'Use one of the allowed values.',
            'pattern', 'format' => 'Fix the value so it matches the expected format.',
            'maxLength' => 'Shorten the text.',
            'minLength' => 'Fill in a non-empty value.',
            'type' => 'Fix the value type (for example a string instead of a number).',
            default => 'Fix the value so it matches data/schema/*.json.',
        };
    }

    /**
     * @param list<string> $pointerParts
     * @param array<string, mixed> $root
     */
    private function linkIdFromPointer(array $pointerParts, array $root): ?string
    {
        if (($pointerParts[0] ?? null) !== 'links' || !isset($pointerParts[1])) {
            return null;
        }

        $index = (int) $pointerParts[1];
        $link = $root['links'][$index] ?? null;

        if (is_array($link) && is_string($link['id'] ?? null)) {
            return $link['id'];
        }

        return "(#{$index})";
    }

    /**
     * @param list<string> $pointerParts
     * @param array<string, mixed> $root
     */
    private function roomLabelFromPointer(array $pointerParts, array $root): ?string
    {
        if (($pointerParts[0] ?? null) !== 'rooms' || !isset($pointerParts[1])) {
            return null;
        }

        $index = (int) $pointerParts[1];
        $room = $root['rooms'][$index] ?? null;

        if (is_array($room) && is_string($room['id'] ?? null)) {
            return $room['id'];
        }

        return "(#{$index})";
    }

    /**
     * @return list<string> matched tracking parameter names, empty if none found
     */
    private function findTrackingParams(string $url): array
    {
        $query = parse_url($url, PHP_URL_QUERY);
        if (!is_string($query) || $query === '') {
            return [];
        }

        parse_str($query, $params);

        $matches = [];
        foreach (array_keys($params) as $name) {
            foreach (self::TRACKING_PARAM_PATTERNS as $pattern) {
                if (preg_match($pattern, (string) $name) === 1) {
                    $matches[] = (string) $name;
                    break;
                }
            }
        }

        return $matches;
    }

    private function normalizeUrl(string $url): string
    {
        $url = strtolower(trim($url));

        return rtrim($url, '/');
    }

    /**
     * @return ValidationError[]
     */
    private function validateIcon(string $icon, string $relFile, string $label): array
    {
        $errors = [];
        $extension = strtolower(pathinfo($icon, PATHINFO_EXTENSION));

        if (!in_array($extension, self::ALLOWED_ICON_EXTENSIONS, true)) {
            $errors[] = new ValidationError(
                $relFile,
                $label,
                "icon '{$icon}' has an unsupported file type.",
                'Use a PNG, SVG or WebP file.',
            );

            return $errors;
        }

        $iconPath = $this->paths->iconsDir() . '/' . $icon;

        if (!is_file($iconPath)) {
            $errors[] = new ValidationError(
                $relFile,
                $label,
                "icon file 'public/link-icons/{$icon}' does not exist.",
                'Add the icon file, run bin/fetch-icons.php, or remove the icon field to use the fallback tile.',
            );

            return $errors;
        }

        $size = filesize($iconPath);
        if ($size !== false && $size > self::MAX_ICON_BYTES) {
            $maxKb = (int) (self::MAX_ICON_BYTES / 1024);
            $errors[] = new ValidationError(
                $relFile,
                $label,
                "icon file 'public/link-icons/{$icon}' is larger than {$maxKb} KB.",
                'Use a smaller or more compressed icon file.',
            );
        }

        $dimensions = $this->iconDimensions($iconPath, $extension);
        if ($dimensions !== null && $dimensions[0] !== $dimensions[1]) {
            [$width, $height] = $dimensions;
            $errors[] = new ValidationError(
                $relFile,
                $label,
                "icon file 'public/link-icons/{$icon}' is not square ({$width}x{$height}px).",
                'Use a square icon (equal width and height).',
            );
        }

        return $errors;
    }

    /**
     * @return array{0: float, 1: float}|null [width, height], or null when it could not be determined
     */
    private function iconDimensions(string $path, string $extension): ?array
    {
        if ($extension === 'svg') {
            return $this->svgDimensions($path);
        }

        $info = @getimagesize($path);
        if ($info === false) {
            return null;
        }

        return [(float) $info[0], (float) $info[1]];
    }

    /**
     * @return array{0: float, 1: float}|null
     */
    private function svgDimensions(string $path): ?array
    {
        $xml = @simplexml_load_file($path);
        if ($xml === false) {
            return null;
        }

        $attributes = $xml->attributes();
        $width = isset($attributes['width']) ? (float) $attributes['width'] : null;
        $height = isset($attributes['height']) ? (float) $attributes['height'] : null;

        if ($width !== null && $height !== null && $width > 0 && $height > 0) {
            return [$width, $height];
        }

        if (isset($attributes['viewBox'])) {
            $parts = preg_split('/[\s,]+/', trim((string) $attributes['viewBox'])) ?: [];
            if (count($parts) === 4) {
                $viewWidth = (float) $parts[2];
                $viewHeight = (float) $parts[3];
                if ($viewWidth > 0 && $viewHeight > 0) {
                    return [$viewWidth, $viewHeight];
                }
            }
        }

        return null;
    }
}
