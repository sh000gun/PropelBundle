<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Bundle\PropelBundle\Controller;

use Propel\Runtime\Propel;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Profiler\Profiler;

use Twig\Environment;
/**
 * PanelController is designed to display information in the Propel Panel.
 *
 * @author William DURAND <william.durand1@gmail.com>
 */
class PanelController
{
    /**
     * This method injects necessary services into controller
     */
    public function __construct(Profiler $profiler = null, Environment $twig, array $configuration, bool $logging)
    {
        $this->profiler = $profiler;
        $this->twig = $twig;
        $this->configuration = $configuration;
        $this->logging = $logging;
    }

    /**
     * This method renders the global Propel configuration.
     */
    public function configurationAction()
    {
        return new Response($this->twig->render(
            '@Propel/Panel/configuration.html.twig',
            array(
                'propel_version'     => Propel::VERSION,
                'configuration'      => $this->configuration,
                'logging'            => $this->logging)
            ),
            200,
            ['Content-Type' => 'text/html']
        );
    }

    /**
     * Renders the profiler panel for the given token.
     *
     * @param string  $token      The profiler token
     * @param string  $connection The connection name
     * @param integer $query
     *
     * @return Response A Response instance
     */
    public function explainAction($token, $connection, $query)
    {
        $profiler = $this->profiler;
        $profiler->disable();

        $profile = $profiler->loadProfile($token);
        $queries = $profile->getCollector('propel')->getQueries();

        if (!isset($queries[$query])) {
            return new Response('This query does not exist.');
        }

        // Open the connection
        $con = Propel::getConnection($connection);

        try {
            $dataFetcher = $con->query('EXPLAIN ' . $queries[$query]['sql']);
            $results = array();
            while (($results[] = $dataFetcher->fetch(\PDO::FETCH_ASSOC)));
        } catch (\Exception $e) {
            return new Response('<div class="error">This query cannot be explained.</div>');
        }

        return new Response($this->twig->render(
            '@Propel/Panel/explain.html.twig',
            array(
                'data' => $results,
                'query' => $query,)
            ),
            200,
            ['Content-Type' => 'text/html']
        );
    }
}
