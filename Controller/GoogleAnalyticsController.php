<?php

namespace Kunstmaan\DashboardBundle\Controller;

use Kunstmaan\DashboardBundle\Entity\AnalyticsConfig;
use Kunstmaan\DashboardBundle\Entity\AnalyticsOverview;
use Kunstmaan\DashboardBundle\Entity\AnalyticsSegment;
use Kunstmaan\DashboardBundle\Helper\Google\Analytics\ConfigHelper;
use Kunstmaan\DashboardBundle\Helper\Google\ClientHelper;
use Kunstmaan\DashboardBundle\Repository\AnalyticsConfigRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

final class GoogleAnalyticsController extends AbstractController
{
    /** @var AnalyticsConfig */
    private $analyticsConfig;
    /** @var ClientHelper */
    private $clientHelper;

    public function __construct(ConfigHelper $analyticsConfig, ClientHelper $clientHelper)
    {
        $this->analyticsConfig = $analyticsConfig;
        $this->clientHelper = $clientHelper;
    }

    /**
     * The index action will render the main screen the users see when they log in in to the admin
     *
     * @Route("/", name="KunstmaanDashboardBundle_widget_googleanalytics")
     * @Template("@KunstmaanDashboard/GoogleAnalytics/widget.html.twig")
     *
     * @return array
     */
    public function widgetAction(Request $request)
    {
        $params['redirect_uri'] = $this->get('router')->generate('KunstmaanDashboardBundle_setToken', [], UrlGeneratorInterface::ABSOLUTE_URL);

        // if token not set
        if (!$this->analyticsConfig->tokenIsSet()) {
            if ($this->getParameter('kunstmaan_dashboard.google_analytics.api.client_id') != '' && $this->getParameter('kunstmaan_dashboard.google_analytics.api.client_secret') != '' && $this->getParameter('kunstmaan_dashboard.google_analytics.api.dev_key') != '') {
                $params['authUrl'] = $this->analyticsConfig->getAuthUrl();
            }

            return $this->render('@KunstmaanDashboard/GoogleAnalytics/connect.html.twig', $params);
        }

        // if propertyId not set
        if (!$this->analyticsConfig->accountIsSet()) {
            return $this->redirect($this->generateUrl('KunstmaanDashboardBundle_Config'));
        }

        // if propertyId not set
        if (!$this->analyticsConfig->propertyIsSet()) {
            return $this->redirect($this->generateUrl('KunstmaanDashboardBundle_PropertySelection'));
        }

        // if profileId not set
        if (!$this->analyticsConfig->profileIsSet()) {
            return $this->redirect($this->generateUrl('KunstmaanDashboardBundle_ProfileSelection'));
        }

        $em = $this->getDoctrine()->getManager();

        // get the segment id
        $segmentId = $request->query->get('id');
        $params['segments'] = $em->getRepository(AnalyticsConfig::class)->findFirst()->getSegments();
        $params['segmentId'] = $segmentId;

        // set the overviews param
        $params['token'] = true;
        if ($segmentId) {
            $overviews = $em->getRepository(AnalyticsSegment::class)->find($segmentId)->getOverviews();
        } else {
            $overviews = $em->getRepository(AnalyticsOverview::class)->getDefaultOverviews();
        }

        $params['disableGoals'] = $em->getRepository(AnalyticsConfig::class)->findFirst()->getDisableGoals();
        $params['overviews'] = $overviews;
        /** @var AnalyticsConfigRepository $analyticsConfigRepository */
        $analyticsConfigRepository = $em->getRepository(AnalyticsConfig::class);
        $date = $analyticsConfigRepository->findFirst()->getLastUpdate();
        if ($date) {
            $params['last_update'] = $date->format('d-m-Y H:i');
        } else {
            $params['last_update'] = 'never';
        }

        return $params;
    }

    /**
     * @Route("/setToken/", name="KunstmaanDashboardBundle_setToken")
     *
     * @throws AccessDeniedException
     *
     * @return RedirectResponse
     */
    public function setTokenAction(Request $request)
    {
        $this->denyAccessUnlessGranted('ROLE_SUPER_ADMIN');

        $codeParameter = $request->query->get('code');

        if (null !== $codeParameter) {
            $code = urldecode($codeParameter);

            $this->clientHelper->getClient()->authenticate($code);
            $this->analyticsConfig->saveToken($this->clientHelper->getClient()->getAccessToken());

            return $this->redirect($this->generateUrl('KunstmaanDashboardBundle_Config'));
        }

        return $this->redirect($this->generateUrl('KunstmaanDashboardBundle_widget_googleanalytics'));
    }

    /**
     * @Route("/config", name="KunstmaanDashboardBundle_Config")
     *
     * @throws AccessDeniedException
     *
     * @return Response
     */
    public function configAction(Request $request)
    {
        $this->denyAccessUnlessGranted('ROLE_SUPER_ADMIN');

        $params = [];

        if (null !== $request->request->get('accounts')) {
            return $this->redirect($this->generateUrl('kunstmaan_dashboard'));
        }

        $em = $this->getDoctrine()->getManager();
        $config = $em->getRepository(AnalyticsConfig::class)->findFirst();

        $params['accountId'] = $config->getAccountId();
        $params['propertyId'] = 0;
        $params['profileId'] = 0;
        $params['properties'] = [];
        $params['profiles'] = [];

        if ($params['accountId']) {
            $params['propertyId'] = $config->getPropertyId();
            $params['properties'] = $this->analyticsConfig->getProperties();

            $params['profileId'] = $config->getProfileId();
            $params['profiles'] = $this->analyticsConfig->getProfiles();
        }

        $params['accounts'] = $this->analyticsConfig->getAccounts();
        $params['segments'] = $config->getSegments();
        $params['disableGoals'] = $config->getDisableGoals();
        $params['configId'] = $config->getId();

        $params['profileSegments'] = $this->analyticsConfig->getProfileSegments();

        return $this->render(
            '@KunstmaanDashboard/GoogleAnalytics/setupcontainer.html.twig',
            $params
        );
    }

    /**
     * @Route("/resetProfile", name="KunstmaanDashboardBundle_analytics_resetProfile")
     *
     * @throws AccessDeniedException
     */
    public function resetProfileAction()
    {
        $this->denyAccessUnlessGranted('ROLE_SUPER_ADMIN');

        $em = $this->getDoctrine()->getManager();
        $em->getRepository(AnalyticsConfig::class)->resetProfileId();

        return $this->redirect($this->generateUrl('KunstmaanDashboardBundle_ProfileSelection'));
    }

    /**
     * @Route("/resetProperty", name="KunstmaanDashboardBundle_analytics_resetProperty")
     *
     * @throws AccessDeniedException
     */
    public function resetPropertyAction()
    {
        $this->denyAccessUnlessGranted('ROLE_SUPER_ADMIN');

        $em = $this->getDoctrine()->getManager();
        $em->getRepository(AnalyticsConfig::class)->resetPropertyId();

        return $this->redirect($this->generateUrl('KunstmaanDashboardBundle_Config'));
    }
}
