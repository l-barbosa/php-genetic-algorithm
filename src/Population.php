<?php

declare(strict_types=1);

namespace Voodooism\Genetic;

use Voodooism\Genetic\DNA\AbstractDNA;
use Voodooism\Genetic\DNA\NullDNA;
use InvalidArgumentException;

class Population
{
    /**
     * Array of DNA.
     *
     * @var AbstractDNA[]
     */
    private array $population;

    /**
     * Current epoch.
     */
    private int $epoch;

    /**
     * It is the probability of gene mutation.
     * From 0 to 1.
     *
     * Usually, it makes sense about from 0.1 to 0.2.
     *
     * IMPORTANT: If you pick 1 this will make the algorithm look like brute force.
     */
    private float $mutationRate;

    /**
     * The best DNA of current population.
     */
    private ?AbstractDNA $best = null;

    /**
     * Sum of fitness of all the population genes.
     */
    private float $totalFitness;

    /**
     * Number of DNA in the population.
     */
    private int $populationNumber;

    public function __construct(AbstractDNA $DNA, int $populationNumber, float $mutationRate = 0)
    {
        if ($mutationRate < 0 || $mutationRate > 1) {
            throw new InvalidArgumentException(
                'Mutation rate should be 0 or less than 1'
            );
        }

        $this->epoch = 0;
        $this->mutationRate = $mutationRate;
        $this->totalFitness = 0;
        $this->populationNumber = $populationNumber;

        $this->population = [];
        for ($i = 0; $i < $populationNumber; $i++) {
            $this->population[] = $DNA->replicate();
        }
    }

    /**
     * Returns the best DNA of this population.
     */
    public function getBest(): AbstractDNA
    {
        return $this->best ?? new NullDNA();
    }

    /**
     * Returns sum of fitness of all the population genes.
     */
    public function getTotalFitness(): float
    {
        return $this->totalFitness;
    }

    /**
     * Returns current step of evolution.
     */
    public function getEpoch(): int
    {
        return $this->epoch;
    }

    /**
     * Increments the generation
     */
    public function incrementEpoch(): void
    {
        $this->epoch += 1;
    }

    /**
     * Returns population number.
     */
    public function getPopulationNumber(): int
    {
        return $this->populationNumber;
    }

    /**
     * Returns mutation rate
     */
    public function getMutationRate(): float
    {
        return $this->mutationRate;
    }

    /**
     * Returns array of all the population genes.
     *
     * @return AbstractDNA[]
     */
    public function getPopulation(): array
    {
        return $this->population;
    }

    /**
     * Sets a new population
     */
    public function setPopulation(array $newPopulation): void
    {
        $this->population = $newPopulation;
    }

    /**
     * Evaluates the fitness of the whole population.
     */
    public function evaluateFitness(): void
    {
        $this->totalFitness = 0;
        $this->best = null;
        foreach ($this->population as $DNA) {
            $DNA->evaluateFitness();
            $this->totalFitness += $DNA->getFitness();

            if (!$this->best || $this->best->getFitness() < $DNA->getFitness()) {
                $this->best = $DNA;
            }
        }
    }

    /**
     * Creates a new generation of DNA.
     *
     * 1. Selects parents for a new DNA depends on their fitness.
     * 2. Crossover them to build a new DNA.
     * 3. Mutate the new DNA.
     */
    public function createNewGeneration(): void
    {
        $newGeneration = [];
        for ($i = 0; $i < $this->populationNumber; $i++) {
            $firstParent = $this->selectParent();
            $secondParent = $this->selectParent();

            $child = $firstParent->crossover($secondParent);
            $child->mutate($this->mutationRate);

            $newGeneration[] = $child;
        }

        $this->population = $newGeneration;
        $this->epoch++;
    }

    /**
     * Selects a parent from current population depends on fitness.
     */
    private function selectParent(): AbstractDNA
    {
        $random = mt_rand() / mt_getrandmax();
        $index = 0;

        while ($random > 0) {
            $random -= $this->population[$index]->getFitness() / $this->totalFitness;
            $index++;
        }

        $index--;
        return $this->population[$index];
    }
}