<?php
namespace Kunstmaan\DashboardBundle\Command\Helper\Analytics;


use Kunstmaan\DashboardBundle\Entity\AnalyticsOverview;

class UsersCommandHelper extends AbstractAnalyticsCommandHelper
{

    /**
     * get data and save it for the overview
     *
     * @param AnalyticsOverview $overview The overview
     */
    public function getData(&$overview)
    {
        // visitor types
        $this->output->writeln("\t" . 'Fetching visitor types..');

        if ($overview->getUseYear()) {
            $begin = date('Y-m-d', mktime(0, 0, 0, 1, 1, date('Y')));
            $end = date('Y-m-d', strtotime("-1 days"));
            $results = $this->analyticsHelper->getResultsByDate(
                $begin,
                $end,
                'ga:percentNewSessions'
            );
        } else {
            $results = $this->analyticsHelper->getResults(
                $overview->getTimespan(),
                $overview->getStartOffset(),
                'ga:percentNewSessions'
            );
        }


        $rows = $results->getRows();

        // new sessions
        $newUsers = is_array($rows) && isset($rows[0][0]) ? $rows[0][0] : 0;
        $overview->setNewUsers($newUsers);
    }


}
