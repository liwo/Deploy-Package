<?php
namespace TYPO3\Deploy\Task;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3.Deploy".               *
 *                                                                        *
 *                                                                        */

use \TYPO3\Deploy\Domain\Model\Node;
use \TYPO3\Deploy\Domain\Model\Application;
use \TYPO3\Deploy\Domain\Model\Deployment;

use Doctrine\ORM\Mapping as ORM;
use TYPO3\FLOW3\Annotations as FLOW3;

/**
 * Task for managing Varnish
 *
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class VarnishPurgeTask extends \TYPO3\Deploy\Domain\Model\Task {

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\Deploy\Domain\Service\ShellCommandService
	 */
	protected $shell;

	/**
	 * Execute this task
	 *
	 * @param \TYPO3\Deploy\Domain\Model\Node $node
	 * @param \TYPO3\Deploy\Domain\Model\Application $application
	 * @param \TYPO3\Deploy\Domain\Model\Deployment $deployment
	 * @param array $options
	 * @return void
	 */
	public function execute(Node $node, Application $application, Deployment $deployment, array $options = array()) {
		$this->checkOptionsForValidity($options);

		$secretFile = (isset($options['secretFile']) ? $options['secretFile'] : '/etc/varnish/secret');
		$purgeUrl = (isset($options['purgeUrl']) ? $options['purgeUrl'] : '.');
		$varnishadm = (isset($options['varnishadm']) ? $options['varnishadm'] : '/usr/bin/varnishadm');

		$this->shell->executeOrSimulate($varnishadm . ' -S ' . $secretFile . ' -T 127.0.0.1:6082 url.purge ' . $purgeUrl, $node, $deployment);
	}

	/**
	 * Simulate this task
	 *
	 * @param Node $node
	 * @param Application $application
	 * @param Deployment $deployment
	 * @param array $options
	 * @return void
	 */
	public function simulate(Node $node, Application $application, Deployment $deployment, array $options = array()) {
		$this->checkOptionsForValidity($options);

		$secretFile = (isset($options['secretFile']) ? $options['secretFile'] : '/etc/varnish/secret');
		$purgeUrl = (isset($options['purgeUrl']) ? $options['purgeUrl'] : '.');
		$varnishadm = (isset($options['varnishadm']) ? $options['varnishadm'] : '/usr/bin/varnishadm');

		$this->shell->executeOrSimulate($varnishadm . ' -S ' . $secretFile . ' -T 127.0.0.1:6082 status', $node, $deployment);
	}

	/**
	 * Check if all required options are given
	 *
	 * @param array $options
	 */
	protected function checkOptionsForValidity($options) {
	}
}
?>