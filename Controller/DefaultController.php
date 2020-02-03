<?php

namespace Mpclarkson\ResqueBundle\Controller;

use Mpclarkson\ResqueBundle\Resque;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class DefaultController
 * @package Mpclarkson\ResqueBundle\Controller
 */
class DefaultController extends Controller
{
    /**
     * @return Response
     */
    public function indexAction()
    {
        $this->getResque()->pruneDeadWorkers();

        return $this->render(
            '@Resque/Default/index.html.twig',
            [
                'resque' => $this->getResque(),
            ]
        );
    }

    /**
     * @return Resque
     */
    protected function getResque()
    {
        return $this->get('resque');
    }

    /**
     * @param $queue
     * @param Request $request
     * @return Response
     */
    public function showQueueAction($queue, Request $request)
    {
        list($start, $count, $showingAll) = $this->getShowParameters($request);

        $queue = $this->getResque()->getQueue($queue);
        $jobs = $queue->getJobs($start, $count);

        if (!$showingAll) {
            $jobs = array_reverse($jobs);
        }

        return $this->render(
            '@Resque/Default/queue_show.html.twig',
            [
                'queue'      => $queue,
                'jobs'       => $jobs,
                'showingAll' => $showingAll
            ]
        );
    }

    /**
     * Decide which parts of a job queue to show
     *
     * @param Request $request
     *
     * @return array
     */
    private function getShowParameters(Request $request)
    {
        $showingAll = FALSE;
        $start = -100;
        $count = -1;

        if ($request->query->has('all')) {
            $start = 0;
            $count = -1;
            $showingAll = TRUE;
        }

        return [$start, $count, $showingAll];
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function listFailedAction(Request $request)
    {
        list($start, $count, $showingAll) = $this->getShowParameters($request);

        $jobs = $this->getResque()->getFailedJobs($start, $count);

        if (!$showingAll) {
            $jobs = array_reverse($jobs);
        }

        return $this->render(
            '@Resque/Default/failed_list.html.twig',
            [
                'jobs'       => $jobs,
                'showingAll' => $showingAll,
            ]
        );
    }

    /**
     * @return Response
     */
    public function listScheduledAction()
    {
        return $this->render(
            '@Resque/Default/scheduled_list.html.twig',
            [
                'timestamps' => $this->getResque()->getDelayedJobTimestamps()
            ]
        );
    }

    /**
     * @param $timestamp
     * @return Response
     */
    public function showTimestampAction($timestamp)
    {
        $jobs = [];

        // we don't want to enable the twig debug extension for this...
        foreach ($this->getResque()->getJobsForTimestamp($timestamp) as $job) {
            $jobs[] = print_r($job, TRUE);
        }

        return $this->render(
            '@Resque/Default/scheduled_timestamp.html.twig',
            [
                'timestamp' => $timestamp,
                'jobs'      => $jobs
            ]
        );
    }
}
