<?php

namespace Kunstmaan\DashboardBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Kunstmaan\DashboardBundle\Entity\AnalyticsConfig;
use Kunstmaan\DashboardBundle\Entity\AnalyticsOverview;

/**
 * AnalyticsConfigRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class AnalyticsConfigRepository extends EntityRepository
{
    /**
     * Get the config from the database, creates a new entry if the config doesn't exist yet
     *
     * @return AnalyticsConfig $config
     */
    public function getConfig($id=false)
    {
        $em = $this->getEntityManager();
        $qb = $this->getEntityManager()->createQueryBuilder();
        if ($id) {
            $qb->select('c')
              ->from('KunstmaanDashboardBundle:AnalyticsConfig', 'c')
              ->where('c.id = :id')
              ->setParameter('id', $id);

            $result = $qb->getQuery()->getResult();
            $config = $result[0];
        } else {
            $query = $em->createQuery(
              'SELECT c FROM KunstmaanDashboardBundle:AnalyticsConfig c'
            );

            $result = $query->getResult();

            if (!$result) {
                return $this->createConfig();
            } else {
                $config = $result[0];
            }
        }

        return $config;
    }

    public function listConfigs() {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('c')
          ->from('KunstmaanDashboardBundle:AnalyticsConfig', 'c');
        return $qb->getQuery()->getResult();
    }

    public function createConfig() {
        $em = $this->getEntityManager();

        $config = new AnalyticsConfig();
        $em->persist($config);
        $em->flush();

        $this->addOverviews($config);
        return $config;
    }

    private function addOverviews($config) {
        $em = $this->getEntityManager();

        $today = new AnalyticsOverview();
        $today->setTitle('dashboard.ga.tab.today');
        $today->setTimespan(0);
        $today->setStartOffset(0);
        $today->setConfig($config);
        $em->persist($today);

        $yesterday = new AnalyticsOverview();
        $yesterday->setTitle('dashboard.ga.tab.yesterday');
        $yesterday->setTimespan(1);
        $yesterday->setStartOffset(1);
        $yesterday->setConfig($config);
        $em->persist($yesterday);

        $week = new AnalyticsOverview();
        $week->setTitle('dashboard.ga.tab.last_7_days');
        $week->setTimespan(7);
        $week->setStartOffset(0);
        $week->setConfig($config);
        $em->persist($week);

        $month = new AnalyticsOverview();
        $month->setTitle('dashboard.ga.tab.last_30_days');
        $month->setTimespan(30);
        $month->setStartOffset(0);
        $month->setConfig($config);
        $em->persist($month);

        $year = new AnalyticsOverview();
        $year->setTitle('dashboard.ga.tab.last_12_months');
        $year->setTimespan(365);
        $year->setStartOffset(0);
        $year->setConfig($config);
        $em->persist($year);

        $yearToDate = new AnalyticsOverview();
        $yearToDate->setTitle('dashboard.ga.tab.year_to_date');
        $yearToDate->setTimespan(365);
        $yearToDate->setStartOffset(0);
        $yearToDate->setConfig($config);
        $yearToDate->setUseYear(true);
        $em->persist($yearToDate);

        $em->flush();
    }

    /** Update the timestamp when data is collected */
<<<<<<< HEAD
    public function setUpdated($id=false) {
        $em = $this->getEntityManager();
        $config = $this->getConfig($id);
=======
    public function setUpdated()
    {
        $em = $this->getEntityManager();
        $config = $this->getConfig();
>>>>>>> origin/master
        $config->setLastUpdate(new \DateTime());
        $em->persist($config);
        $em->flush();
    }

    /**
     * saves the token
     *
     * @param string $token
     */
<<<<<<< HEAD
    public function saveToken($token, $id=false) {
        $em    = $this->getEntityManager();
        $config = $this->getConfig($id);
=======
    public function saveToken($token)
    {
        $em = $this->getEntityManager();
        $config = $this->getConfig();
>>>>>>> origin/master
        $config->setToken($token);
        $em->persist($config);
        $em->flush();
    }

    /**
     * saves the property id
     *
     * @param string $propertyId
     */
<<<<<<< HEAD
    public function savePropertyId($propertyId, $id=false) {
        $em    = $this->getEntityManager();
        $config = $this->getConfig($id);
=======
    public function savePropertyId($propertyId)
    {
        $em = $this->getEntityManager();
        $config = $this->getConfig();
>>>>>>> origin/master
        $config->setPropertyId($propertyId);
        $em->persist($config);
        $em->flush();
    }

    /**
     * saves the account id
     *
     * @param string $accountId
     */
<<<<<<< HEAD
    public function saveAccountId($accountId, $id=false) {
        $em    = $this->getEntityManager();
        $config = $this->getConfig($id);
=======
    public function saveAccountId($accountId)
    {
        $em = $this->getEntityManager();
        $config = $this->getConfig();
>>>>>>> origin/master
        $config->setAccountId($accountId);
        $em->persist($config);
        $em->flush();
    }

    /**
     * saves the profile id
     *
     * @param string $profileId
     */
<<<<<<< HEAD
    public function saveProfileId($profileId, $id=false) {
        $em    = $this->getEntityManager();
        $config = $this->getConfig($id);
=======
    public function saveProfileId($profileId)
    {
        $em = $this->getEntityManager();
        $config = $this->getConfig();
>>>>>>> origin/master
        $config->setProfileId($profileId);
        $em->persist($config);
        $em->flush();
    }

    /**
     * saves the config name
     *
     * @param string $profileId
     */
    public function saveConfigName($name, $id=false) {
        $em    = $this->getEntityManager();
        $config = $this->getConfig($id);
        $config->setName($name);
        $em->persist($config);
        $em->flush();
    }

    /** resets the profile id */
<<<<<<< HEAD
    public function resetProfileId($id=false) {
        $em    = $this->getEntityManager();
        $config = $this->getConfig($id);
=======
    public function resetProfileId()
    {
        $em = $this->getEntityManager();
        $config = $this->getConfig();
>>>>>>> origin/master
        $config->setProfileId('');
        $em->persist($config);
        $em->flush();
    }

    /** resets the  account id, property id and profile id */
<<<<<<< HEAD
    public function resetPropertyId($id=false) {
        $em    = $this->getEntityManager();
        $config = $this->getConfig($id);
=======
    public function resetPropertyId()
    {
        $em = $this->getEntityManager();
        $config = $this->getConfig();
>>>>>>> origin/master
        $config->setAccountId('');
        $config->setProfileId('');
        $config->setPropertyId('');
        $em->persist($config);
        $em->flush();
    }
}
