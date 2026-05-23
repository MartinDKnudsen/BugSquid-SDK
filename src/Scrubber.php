<?php

namespace BugSquid;

final class Scrubber
{
    public function scrub(array $data, array $fields): array
    {
        $lower = array_map('strtolower', $fields);

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->scrub($value, $fields);
            } elseif (in_array(strtolower((string) $key), $lower, true)) {
                $data[$key] = '[Filtered]';
            }
        }

        return $data;
    }
}
