<?php
namespace TYPO3\Deploy\Task\FLOW3;

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
 * A symlink task for linking the shared data directory
 *
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class SymlinkDataTask extends \TYPO3\Deploy\Domain\Model\Task {

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\Deploy\Domain\Service\ShellCommandService
	 */
	protected $shell;

	/**
	 * Executes this task
	 *
	 * @param \TYPO3\Deploy\Domain\Model\Node $node
	 * @param \TYPO3\Deploy\Domain\Model\Application $application
	 * @param \TYPO3\Deploy\Domain\Model\Deployment $deployment
	 * @param array $options
	 * @return void
	 */
	public function execute(Node $node, Application $application, Deployment $deployment, array $options = array()) {
		$releaseIdentifier = $deployment->getReleaseIdentifier();
		$releasesPath = $application->getDeploymentPath() . '/releases';
		$commands = array(
			"mkdir -p $releasesPath/$releaseIdentifier/Data",
			"cd $releasesPath/$releaseIdentifier",
			"ln -sf ../../../shared/Data/Logs ./Data/Logs",
			"ln -sf ../../../shared/Data/Persistent ./Data/Persistent"
		);
		$this->shell->executeOrSimulate($commands, $node, $deployment);
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
		$this->execute($node, $application, $deployment, $options);
	}

}
?>