<?php

namespace Kunstmaan\DashboardBundle\Tests\Entity;

use Kunstmaan\DashboardBundle\Entity\AnalyticsGoal;
use PHPUnit\Framework\TestCase;

class AnalyticsGoalTest extends TestCase
{
    public function testGettersAndSetters()
    {
        $entity = new AnalyticsGoal();
        $entity->setId(666);
        $entity->setOverview(5);
        $entity->setPosition(6);
        $entity->setName('Donald Trump');
        $entity->setVisits(7);
        $entity->setChartData('blahblah');

        $this->assertEquals(666, $entity->getId());
        $this->assertEquals(5, $entity->getOverview());
        $this->assertEquals(6, $entity->getPosition());
        $this->assertEquals('Donald Trump', $entity->getName());
        $this->assertEquals(7, $entity->getVisits());
        $this->assertEquals('blahblah', $entity->getChartData());
    }
}
