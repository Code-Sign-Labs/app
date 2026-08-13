<?php

declare(strict_types=1);

namespace Framework\Addons\Prometheus;

use Redis;

class MetricRegistry
{
    protected const PREFIX = 'prom:';
    protected const SERIES_COUNTERS = self::PREFIX . 'series:counters';
    protected const SERIES_GAUGES = self::PREFIX . 'series:gauges';
    protected const SERIES_HISTOGRAMS = self::PREFIX . 'series:histograms';

    protected const DEFAULT_HISTOGRAM_BUCKETS = [0.5, 1, 2.5, 5, 10];

    public function __construct(protected Redis $client)
    {
    }

    public function counter(string $name, array $labels = [], int $value = 1): void
    {
        if (empty($name)) {
            return;
        }

        $series = $this->seriesKey($name, $labels);

        $this->client->sAdd(self::SERIES_COUNTERS, $series);
        $this->client->incrBy($this->redisKey('counter', $series), $value);
    }

    public function gauge(string $name, float $value, array $labels = []): void
    {
        if (empty($name)) {
            return;
        }

        $series = $this->seriesKey($name, $labels);

        $this->client->sAdd(self::SERIES_GAUGES, $series);
        $this->client->set($this->redisKey('gauge', $series), (string) $value);
    }

    public function histogram(string $name, float $value, array $labels = []): void
    {
        if ($name === '') {
            return;
        }

        $series = $this->seriesKey($name, $labels);

        $this->client->sAdd(self::SERIES_HISTOGRAMS, $series);

        $key = $this->redisKey('histogram', $series);

        foreach (self::DEFAULT_HISTOGRAM_BUCKETS as $bucket) {
            if ($value <= $bucket) {
                $this->client->hIncrBy(
                    $key,
                    'le=' . $this->bucketToString($bucket),
                    1
                );
            }
        }

        // Every observation belongs to +Inf.
        $this->client->hIncrBy($key, 'le=+Inf', 1);

        // Histogram sum/count.
        $this->client->hIncrByFloat(
            $key,
            'sum',
            $value
        );

        $this->client->hIncrBy(
            $key,
            'count',
            1
        );
    }

    protected function bucketToString(float $bucket): string
    {
        return (string) $bucket;
    }

    public function render(): string
    {
        $out = [];

        // COUNTERS
        $counters = $this->client->sMembers(self::SERIES_COUNTERS) ?: [];
        foreach ($counters as $series) {
            [$name, $labels] = $this->decodeSeries((string) $series);
            if ($name === '') continue;

            $value = $this->client->get($this->redisKey('counter', (string) $series)) ?? '0';
            $out[] = sprintf('%s%s %s', $name, $labels, $value);
        }

        // GAUGES
        $gauges = $this->client->sMembers(self::SERIES_GAUGES) ?: [];
        foreach ($gauges as $series) {
            [$name, $labels] = $this->decodeSeries((string) $series);
            if ($name === '') continue;

            $value = (float) ($this->client->get($this->redisKey('gauge', (string) $series)) ?? 0);
            $out[] = sprintf('%s%s %f', $name, $labels, $value);
        }

        // HISTOGRAMS
        $histograms = $this->client->sMembers(self::SERIES_HISTOGRAMS) ?: [];
        foreach ($histograms as $series) {
            [$baseName, $labels] = $this->decodeSeries((string) $series);
            if ($baseName === '') continue;

            $data = $this->client->hGetAll($this->redisKey('histogram', (string) $series)) ?: [];

            // bucket lines
            foreach ($this->histogramBucketOrder() as $le) {
                $field = 'le=' . $le;
                $count = $data[$field] ?? '0';

                $bucketLabels = $this->mergeLabelsString($labels, ['le' => $le]);
                $out[] = sprintf('%s_bucket%s %s', $baseName, $bucketLabels, $count);
            }

            $sum = $data['sum'] ?? '0';
            $count = $data['count'] ?? '0';

            $out[] = sprintf('%s_sum%s %s', $baseName, $labels, $sum);
            $out[] = sprintf('%s_count%s %s', $baseName, $labels, $count);
        }

        return implode("\n", $out) . "\n";
    }

    protected function seriesKey(string $name, array $labels): string
    {
        ksort($labels);
        return $name . '|' . base64_encode((string) json_encode($labels));
    }

    protected function redisKey(string $type, string $series): string
    {
        return self::PREFIX . $type . ':' . $series;
    }

    /**
     * @return array{0:string,1:string} [name, labelsString]
     */
    protected function decodeSeries(string $series): array
    {
        $parts = explode('|', $series, 2);
        $name = trim($parts[0] ?? '');
        $raw = $parts[1] ?? '';

        if ($name === '' || $name === 'Array') {
            return ['', ''];
        }

        if ($raw === '') {
            return [$name, ''];
        }

        $decodedJson = base64_decode($raw, true);
        $labels = $decodedJson ? json_decode($decodedJson, true) : [];

        if (!is_array($labels) || empty($labels)) {
            return [$name, ''];
        }

        $pairs = [];
        foreach ($labels as $k => $v) {
            $val = is_array($v) ? json_encode($v) : (string) $v;
            $pairs[] = $k . '="' . addslashes($val) . '"';
        }

        return [$name, '{' . implode(',', $pairs) . '}'];
    }

    protected function histogramBucketOrder(): array
    {
        $out = [];
        foreach (self::DEFAULT_HISTOGRAM_BUCKETS as $b) {
            $out[] = $this->bucketToString($b);
        }
        $out[] = '+Inf';
        return $out;
    }

    protected function mergeLabelsString(string $labelsStr, array $extra): string
    {
        $extraPairs = [];
        foreach ($extra as $k => $v) {
            $extraPairs[] = $k . '="' . addslashes((string) $v) . '"';
        }

        if ($labelsStr === '') {
            return '{' . implode(',', $extraPairs) . '}';
        }

        $inner = substr($labelsStr, 1, -1);
        return '{' . $inner . ',' . implode(',', $extraPairs) . '}';
    }
}