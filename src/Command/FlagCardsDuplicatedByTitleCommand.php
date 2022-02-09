<?php

namespace App\Command;

use App\Repository\CardRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FlagCardsDuplicatedByTitleCommand extends Command
{
    protected CardRepository $cardRepository;

    public function __construct(CardRepository $cardRepository)
    {
        parent::__construct();
        $this->cardRepository = $cardRepository;
    }

    protected function configure()
    {
        $this
            ->setName('app:maintenance:flag-cards-duplicated-by-title')
            ->setDescription('Flags cards duplicated by title as such.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // @todo add proper error handling/output [ST 2023/01/07]
        $this->cardRepository->updateIsMultipleFlagOnAllCards();
        $output->writeln('Done.');
        return 0;
    }
}
