<?php
declare(strict_types=1);

namespace dvzHash;

class Benchmark
{
    public const SUBJECT_TYPE_ARGUMENT_SETS = 1;
    public const SUBJECT_TYPE_CLOSURES = 2;

    public const ITERATION_MODE_CASES_IN_SAMPLE = 1;

    public const RANDOMIZATION_PERMUTE_CASES_SIMPLE = 1;

    private int $subjectType;
    private int $iterationMode;
    private int $randomization;
    private int $sampleSize = 1;
    private bool $ran = false;

    // variable mode data
    private \Closure $baseClosure;
    private array $argumentSets = [];

    // closure mode data
    private array $closures;
    private array $arguments = [];

    private int $cases = 0;
    private array $caseSamples;
    private array $caseStatistics;
    private float $totalSampleTime;

    public function __construct(int $subjectType)
    {
        if (in_array($subjectType, [
            Benchmark::SUBJECT_TYPE_ARGUMENT_SETS,
            Benchmark::SUBJECT_TYPE_CLOSURES,
        ])) {
            $this->subjectType = $subjectType;
        } else {
            throw new \Exception('Unsupported subject type provided');
        }

        $this->iterationMode = Benchmark::ITERATION_MODE_CASES_IN_SAMPLE;
        $this->randomization = Benchmark::RANDOMIZATION_PERMUTE_CASES_SIMPLE;
    }

    public function setBaseClosure(callable $closure): void
    {
        $this->baseClosure = $closure;
    }

    public function setClosures(array $closures): void
    {
        foreach ($closures as $i => $closure) {
            if (!is_callable($closure)) {
                throw new \Exception('Provided closure not callable');
            }
        }

        $this->closures = $closures;
        $this->cases = count($closures);
    }

    public function setArgumentSets(array $argumentSets): void
    {
        foreach ($argumentSets as $i => $argumentSet) {
            if (!is_array($argumentSet)) {
                throw new \Exception('Provided argument set is not an array');
            }
        }

        $this->argumentSets = $argumentSets;
        $this->cases = count($argumentSets);
    }

    public function setArguments(array $arguments): void
    {
        $this->arguments = $arguments;
    }

    public function setSampleSize(int $sampleSize): void
    {
        if ($sampleSize < 0) {
            throw new \Exception('Invalid sample size');
        }

        $this->sampleSize = $sampleSize;
    }

    public function setIterationMode(int $iterationMode): void
    {
        if (in_array($iterationMode, [
            Benchmark::ITERATION_MODE_CASES_IN_SAMPLE,
        ])) {
            $this->iterationMode = $iterationMode;
        } else {
            throw new \Exception('Unsupported iteration mode provided');
        }
    }

    public function setRandomization(int $randomization): void
    {
        $this->randomization = $randomization;
    }

    public function getCaseSamples(): array
    {
        if (!$this->ran) {
            $this->run();
        }

        return $this->caseSamples;
    }

    public function getCaseStatistics(): array
    {
        if (!$this->ran) {
            $this->run();
        }

        $caseStatistics = [];

        foreach ($this->caseSamples as $caseSample) {
            $sampleSize = count($caseSample);
            $sampleSum = array_sum($caseSample);

            if ($sampleSize !== 0) {
                $sampleMin = min($caseSample);
                $sampleMax = max($caseSample);
                $sampleRange = $sampleMax - $sampleMin;
                $sampleMidrange = ($sampleMin + $sampleMax) / 2;
                $sampleMean = $sampleSum / $sampleSize;

                if ($sampleSize === 1) {
                    $sampleVariance = 0;
                    $sampleStandardDeviation = 0;
                } else {
                    $degreesOfFreedom = $sampleSize - 1;
                    $sampleVariance = array_sum(
                        array_map(function ($item) use ($sampleMean) {
                            return ($item - $sampleMean) ** 2;
                        }, $caseSample)
                    ) / $degreesOfFreedom;
                    $sampleStandardDeviation = sqrt($sampleVariance);
                }

                $standardErrorOfTheMean = $sampleStandardDeviation / sqrt($sampleSize);
                $relativeStandardError = $standardErrorOfTheMean / $sampleMean;

                if ($sampleMean != 0) {
                    $sampleCoefficientOfVariation = $sampleStandardDeviation / $sampleMean;
                } else {
                    $sampleCoefficientOfVariation = null;
                }

                $caseSampleSorted = $caseSample;
                sort($caseSampleSorted);

                if ($sampleSize % 2 === 0) {
                    $sampleMedian = ($caseSampleSorted[($sampleSize / 2) - 1] + $caseSampleSorted[($sampleSize / 2)]) / 2;
                } else {
                    $sampleMedian = $caseSampleSorted[floor($sampleSize / 2)];
                }

                $interquartileMean = self::getTruncatedMean($caseSampleSorted, 0.25);

                $data = [
                    'coefficientOfVariation' => $sampleCoefficientOfVariation,
                    'interquartileMean' => $interquartileMean,
                    'max' => $sampleMax,
                    'mean' => $sampleMean,
                    'median' => $sampleMedian,
                    'midrange' => $sampleMidrange,
                    'min' => $sampleMin,
                    'range' => $sampleRange,
                    'relativeStandardError' => $relativeStandardError,
                    'standardDeviation' => $sampleStandardDeviation,
                    'standardErrorOfTheMean' => $standardErrorOfTheMean,
                    'sum' => $sampleSum,
                    'variance' => $sampleVariance,
                ];
            } else {
                $data = [];
            }

            $caseStatistics[] = $data;
        }

        $this->caseStatistics = $caseStatistics;

        return $caseStatistics;
    }

    public function getTotalSampleTime(): float
    {
        if (!$this->ran) {
            $this->run();
        }

        return $this->totalSampleTime;
    }

    public function run(): void
    {
        if ($this->subjectType === Benchmark::SUBJECT_TYPE_ARGUMENT_SETS) {
            if (!is_callable($this->baseClosure)) {
                throw new \Exception('No callable base closure provided');
            }
            if (empty($this->argumentSets)) {
                throw new \Exception('No argument sets provided');
            }
        } elseif ($this->subjectType === Benchmark::SUBJECT_TYPE_CLOSURES) {
            if (empty($this->closures)) {
                throw new \Exception('No closures provided');
            }
        }

        $caseSamples = [];
        $totalSampleTime = 0;

        for ($sampleNumber = 1; $sampleNumber <= $this->sampleSize; $sampleNumber++) {
            $caseKeys = range(0, $this->cases - 1);

            if ($this->randomization & Benchmark::RANDOMIZATION_PERMUTE_CASES_SIMPLE) {
                shuffle($caseKeys);
            }

            foreach ($caseKeys as $caseKey) {
                if ($this->subjectType === Benchmark::SUBJECT_TYPE_ARGUMENT_SETS) {
                    $closure = $this->baseClosure;
                    $arguments = $this->argumentSets[$caseKey];
                } elseif ($this->subjectType === Benchmark::SUBJECT_TYPE_CLOSURES) {
                    $closure = $this->closures[$caseKey];
                    $arguments = $this->arguments;
                }

                $executionTime = $executionTime = $this->getExecutionTime($closure, $arguments);

                $caseSamples[$caseKey][] = $executionTime;
                $totalSampleTime += $executionTime;
            }
        }

        ksort($caseSamples);

        $this->caseSamples = $caseSamples;
        $this->totalSampleTime = $totalSampleTime;
        $this->ran = true;
    }

    private static function getTruncatedMean(array $sample, float $truncation = 0.25): float
    {
        $sampleSize = count($sample);

        if ($sampleSize == 1) {
            return $sample[0];
        } else {
            $sampleSorted = $sample;
            sort($sampleSorted);

            $truncationStart = $truncation;
            $truncationEnd = 1 - $truncation;

            $truncationStartPosition = $sampleSize * $truncationStart;
            $truncationEndPosition = $sampleSize * $truncationEnd;

            $truncationStartPositionFraction = $truncationStartPosition - floor($truncationStartPosition);
            $truncationEndPositionFraction = $truncationEndPosition - floor($truncationEndPosition);

            $truncationStartIndex = (int)ceil($truncationStartPosition) - 1;
            $truncationEndIndex = (int)floor($truncationEndPosition) - 1;

            $truncatedSample = [
                $sampleSorted[$truncationStartIndex] +
                (
                    ($sampleSorted[$truncationStartIndex + 1] - $sampleSorted[$truncationStartIndex]) *
                    $truncationStartPositionFraction
                ),
                ...array_slice(
                    $sampleSorted,
                    $truncationStartIndex + 1,
                    $truncationEndIndex - $truncationStartIndex
                ),
                $sampleSorted[$truncationEndIndex] +
                (
                    ($sampleSorted[$truncationEndIndex + 1] - $sampleSorted[$truncationEndIndex]) *
                    $truncationEndPositionFraction
                ),
            ];

            return array_sum($truncatedSample) / count($truncatedSample);
        }
    }

    private function getExecutionTime(\Closure $closure, array $arguments = []): float
    {
        $startTime = microtime(true);

        $closure(...$arguments);

        $endTime = microtime(true);

        return $endTime - $startTime;
    }
}
