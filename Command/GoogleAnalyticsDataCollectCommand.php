<?php

namespace Kunstmaan\DashboardBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use Kunstmaan\DashboardBundle\Command\Helper\Analytics\ChartDataCommandHelper;
use Kunstmaan\DashboardBundle\Command\Helper\Analytics\GoalCommandHelper;
use Kunstmaan\DashboardBundle\Command\Helper\Analytics\MetricsCommandHelper;
use Kunstmaan\DashboardBundle\Command\Helper\Analytics\UsersCommandHelper;
use Kunstmaan\DashboardBundle\Entity\AnalyticsConfig;
use Kunstmaan\DashboardBundle\Entity\AnalyticsOverview;
use Kunstmaan\DashboardBundle\Entity\AnalyticsSegment;
use Kunstmaan\DashboardBundle\Helper\Google\Analytics\ConfigHelper;
use Kunstmaan\DashboardBundle\Helper\Google\Analytics\QueryHelper;
use Kunstmaan\DashboardBundle\Helper\Google\Analytics\ServiceHelper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class GoogleAnalyticsDataCollectCommand extends Command
{
    /** @var EntityManagerInterface */
    private $em;

    /** @var OutputInterface */
    private $output;

    /** @var int */
    private $errors = 0;

    /** @var ServiceHelper */
    private $serviceHelper;
    /** @var ConfigHelper */
    private $configHelper;
    /** @var QueryHelper */
    private $queryHelper;

    public function __construct(EntityManagerInterface $em, ServiceHelper $serviceHelper, ConfigHelper $configHelper, QueryHelper $queryHelper)
    {
        parent::__construct();

        $this->em = $em;
        $this->serviceHelper = $serviceHelper;
        $this->configHelper = $configHelper;
        $this->queryHelper = $queryHelper;
    }

    protected function configure(): void
    {
        $this
            ->setName('kuma:dashboard:widget:googleanalytics:data:collect')
            ->setDescription('Collect the Google Analytics dashboard widget data')
            ->addOption(
                'config',
                null,
                InputOption::VALUE_OPTIONAL,
                'Specify to only update one config',
                false
            )
            ->addOption(
                'segment',
                null,
                InputOption::VALUE_OPTIONAL,
                'Specify to only update one segment',
                false
            )
            ->addOption(
                'overview',
                null,
                InputOption::VALUE_OPTIONAL,
                'Specify to only update one overview',
                false
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->output = $output;

        // check if token is set
        if (!$this->configHelper->tokenIsSet()) {
            $this->output->writeln('You haven\'t configured a Google account yet');

            return Command::SUCCESS;
        }

        // get params
        $configId = false;
        $segmentId = false;
        $overviewId = false;

        try {
            $configId = $input->getOption('config');
            $segmentId = $input->getOption('segment');
            $overviewId = $input->getOption('overview');
        } catch (\Exception $e) {
        }

        // get the overviews
        try {
            $overviews = [];

            if ($overviewId) {
                $overviews[] = $this->getSingleOverview($overviewId);
            } elseif ($segmentId) {
                $overviews = $this->getOverviewsOfSegment($segmentId);
            } elseif ($configId) {
                $overviews = $this->getOverviewsOfConfig($configId);
            } else {
                $overviews = $this->getAllOverviews();
            }

            // update the overviews
            $this->updateData($overviews);
            $result = '<fg=green>Google Analytics data updated with <fg=red>' . $this->errors . '</fg=red> error';
            $result .= $this->errors != 1 ? 's</fg=green>' : '</fg=green>';
            $this->output->writeln($result); // done

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->output->writeln($e->getMessage());

            return Command::FAILURE;
        }
    }

    /**
     * get a single overview
     *
     * @param int $overviewId
     */
    private function getSingleOverview($overviewId): AnalyticsOverview
    {
        // get specified overview
        $overviewRepository = $this->em->getRepository(AnalyticsOverview::class);
        $overview = $overviewRepository->find($overviewId);

        if (!$overview) {
            throw new \Exception('Unkown overview ID');
        }

        return $overview;
    }

    /**
     * get all overviews of a segment
     *
     * @param int $segmentId
     */
    private function getOverviewsOfSegment($segmentId): array
    {
        // get specified segment
        $segmentRepository = $this->em->getRepository(AnalyticsSegment::class);
        $segment = $segmentRepository->find($segmentId);

        if (!$segment) {
            throw new \Exception('Unkown segment ID');
        }

        // init the segment
        $segmentRepository->initSegment($segment);

        // get the overviews
        return $segment->getOverviews();
    }

    /**
     * get all overviews of a config
     *
     * @param int $configId
     */
    private function getOverviewsOfConfig($configId): array
    {
        $configRepository = $this->em->getRepository(AnalyticsConfig::class);
        $segmentRepository = $this->em->getRepository(AnalyticsSegment::class);
        $overviewRepository = $this->em->getRepository(AnalyticsOverview::class);
        // get specified config
        $config = $configRepository->find($configId);

        if (!$config) {
            throw new \Exception('Unkown config ID');
        }

        // create default overviews for this config if none exist yet
        if (!\count($config->getOverviews())) {
            $overviewRepository->addOverviews($config);
        }

        // init all the segments for this config
        $segments = $config->getSegments();
        foreach ($segments as $segment) {
            $segmentRepository->initSegment($segment);
        }

        // get the overviews
        return $config->getOverviews();
    }

    private function getAllOverviews(): array
    {
        $configRepository = $this->em->getRepository(AnalyticsConfig::class);
        $overviewRepository = $this->em->getRepository(AnalyticsOverview::class);
        $segmentRepository = $this->em->getRepository(AnalyticsSegment::class);
        $configs = $configRepository->findAll();

        foreach ($configs as $config) {
            // add overviews if none exist yet
            if (\count($config->getOverviews()) == 0) {
                $overviewRepository->addOverviews($config);
            }

            // init all the segments for this config
            $segments = $config->getSegments();
            foreach ($segments as $segment) {
                $segmentRepository->initSegment($segment);
            }
        }

        // get all overviews
        return $overviewRepository->findAll();
    }

    /**
     * update the overviews
     */
    public function updateData($overviews): void
    {
        // helpers
        $metrics = new MetricsCommandHelper($this->configHelper, $this->queryHelper, $this->output, $this->em);
        $chartData = new ChartDataCommandHelper($this->configHelper, $this->queryHelper, $this->output, $this->em);
        $goals = new GoalCommandHelper($this->configHelper, $this->queryHelper, $this->output, $this->em);
        $visitors = new UsersCommandHelper($this->configHelper, $this->queryHelper, $this->output, $this->em);

        // get data per overview
        foreach ($overviews as $overview) {
            $this->configHelper->init($overview->getConfig()->getId());
            /* @var AnalyticsOverview $overview */
            $this->output->writeln('Fetching data for overview "<fg=green>' . $overview->getTitle() . '</fg=green>"');

            try {
                // metric data
                $metrics->getData($overview);
                if ($overview->getSessions()) { // if there are any visits
                    // day-specific data
                    $chartData->getData($overview);

                    // get goals
                    $goals->getData($overview);

                    // visitor types
                    $visitors->getData($overview);
                } else {
                    // reset overview
                    $this->reset($overview);
                    $this->output->writeln("\t" . 'No visitors');
                }
                // persist entity back to DB
                $this->output->writeln("\t" . 'Persisting..');
                $this->em->persist($overview);
                $this->em->flush();

                $this->em->getRepository(AnalyticsConfig::class)->setUpdated($overview->getConfig()->getId());
            } catch (\Google_ServiceException $e) {
                $error = explode(')', $e->getMessage());
                $error = $error[1];
                $this->output->writeln("\t" . '<fg=red>Invalid segment: </fg=red>' . $error);
                ++$this->errors;
            }
        }
    }

    /**
     * Reset the data for the overview
     */
    private function reset(AnalyticsOverview $overview): void
    {
        // reset overview
        $overview->setNewUsers(0);
        $overview->setReturningUsers(0);
    }
}
