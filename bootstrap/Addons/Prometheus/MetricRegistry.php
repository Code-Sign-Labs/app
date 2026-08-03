<?php

namespace Framework\Addons\Prometheus;

class MetricRegistry
{
    protected const PREFIX = 'prom:';
    protected const SERIES_COUNTERS = self::PREFIX . 'series:counters';
    protected const SERIES_GAUGES = self::PREFIX . 'series:gauges';
    protected const SERIES_HISTOGRAMS = self::PREFIX . 'series:histograms';

    protected const DEFAULT_HISTOGRAM_BUCKETS = [0.5, 1, 2.5, 5, 10];

    public function __construct(protected RedisDriver $driver)
    {
    }

    /**
     * @param string $name
     * @param array $labels
     */
    public function counter(string $name, array $labels = []): void
    {
        $series = $this->seriesKey($name, $labels);
        $client = $this->driver->getClient();

        $client->sadd(self::SERIES_COUNTERS, [$series]);

        $client->incrby($this->redisKey('counter', $series), 1);
    }

    /**
     * @param string $name
     * @param float $value
     * @param array $labels
     */
    public function gauge(string $name, float $value, array $labels = []): void
    {
        $series = $this->seriesKey($name, $labels);
        $client = $this->driver->getClient();

        $client->sadd(self::SERIES_GAUGES, [$series]);
        $client->set($this->redisKey('gauge', $series), (string) $value);
    }

    /**
     * @param string $name
     * @param float $value
     * @param array $labels
     */
    public function histogram(string $name, float $value, array $labels = []): void
    {
        $series = $this->seriesKey($name, $labels);
        $client = $this->driver->getClient();

        $client->sadd(self::SERIES_HISTOGRAMS, [$series]);

        $key = $this->redisKey('histogram', $series);

        foreach (self::DEFAULT_HISTOGRAM_BUCKETS as $bucket) {
            if ($value <= $bucket) {
                $client->hincrby($key, 'le=' . $this->bucketToString($bucket), 1);
                break;
            }
        }
        $client->hincrby($key, 'le=+Inf', 1);

        // sum/count
        $client->hincrbyfloat($key, 'sum', (string) $value);
        $client->hincrby($key, 'count', 1);
    }

    /**
     * @return string
     */
    public function render(): string
    {
        $client = $this->driver->getClient();
        $out = [];

        foreach ($client->smembers(self::SERIES_COUNTERS) as $series) {
            [$name, $labels] = $this->decodeSeries($series);
            $value = $client->get($this->redisKey('counter', $series)) ?? '0';
            $out[] = sprintf('%s%s %s', $name, $labels, $value);
        }

        foreach ($client->smembers(self::SERIES_GAUGES) as $series) {
            [$name, $labels] = $this->decodeSeries($series);
            $value = (float) ($client->get($this->redisKey('gauge', $series)) ?? 0);
            $out[] = sprintf('%s%s %f', $name, $labels, $value);
        }

        foreach ($client->smembers(self::SERIES_HISTOGRAMS) as $series) {
            [$baseName, $labels] = $this->decodeSeries($series);
            $data = $client->hgetall($this->redisKey('histogram', $series));

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

    /**
     * @param string $name
     * @param array $labels
     * @return string
     */
    protected function seriesKey(string $name, array $labels): string
    {
        ksort($labels);
        return $name . '|' . http_build_query($labels);
    }

    /**
     * @param string $type
     * @param string $series
     * @return string
     */
    protected function redisKey(string $type, string $series): string
    {
        return self::PREFIX . $type . ':' . $series;
    }

    /**
     * @return array{0:string,1:string} [name, labelsString]
     */
    protected function decodeSeries(string $series): array
    {
        [$name, $raw] = explode('|', $series, 2);
        if ($raw === '') {
            return [$name, ''];
        }

        parse_str($raw, $labels);
        $pairs = [];
        foreach ($labels as $k => $v) {
            $pairs[] = $k . '="' . $v . '"';
        }

        return [$name, '{' . implode(',', $pairs) . '}'];
    }

    /**
     * @return string[]
     */
    protected function histogramBucketOrder(): array
    {
        $out = [];
        foreach (self::DEFAULT_HISTOGRAM_BUCKETS as $b) {
            $out[] = $this->bucketToString($b);
        }
        $out[] = '+Inf';
        return $out;
    }

    /**
     * @param float $bucket
     * @return string
     */
    protected function bucketToString(float $bucket): string
    {

        $s = rtrim(rtrim((string) $bucket, '0'), '.');
        return $s === '' ? '0' : $s;
    }

    /**
     * @param string $labelsStr
     * @param array $extra
     * @return string
     */
    protected function mergeLabelsString(string $labelsStr, array $extra): string
    {
        if ($labelsStr === '') {
            $pairs = [];
            foreach ($extra as $k => $v) {
                $pairs[] = $k . '="' . $v . '"';
            }
            return '{' . implode(',', $pairs) . '}';
        }

        $inner = substr($labelsStr, 1, -1);
        $pairs = $inner === '' ? [] : [$inner];
        foreach ($extra as $k => $v) {
            $pairs[] = $k . '="' . $v . '"';
        }
        return '{' . implode(',', $pairs) . '}';
    }
}

