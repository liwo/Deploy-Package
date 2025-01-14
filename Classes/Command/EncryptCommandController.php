<?php
declare(ENCODING = 'utf-8');
namespace TYPO3\Deploy\Command;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3.Deploy".               *
 *                                                                        *
 *                                                                        */

use Doctrine\ORM\Mapping as ORM;
use TYPO3\FLOW3\Annotations as FLOW3;

/**
 * Encryption command controller
 */
class EncryptCommandController extends \TYPO3\FLOW3\MVC\Controller\CommandController {

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\Deploy\Encryption\EncryptionServiceInterface
	 */
	protected $encryptionService;

	/**
	 * Setup encryption with a local key for the deployment system
	 *
	 * The local key should be kept secretly and could be encrypted with
	 * an optional passphrase. The name defaults to "Local.key".
	 *
	 * @param string $passphrase Passphrase for the generated key (optional)
	 * @return void
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function setupCommand($passphrase = NULL) {
		if (file_exists($this->getDeploymentConfigurationPath() . '/Keys/Local.key')) {
			$this->outputLine('Local key already exists');
			$this->quit(1);
		}
		$keyPair = $this->encryptionService->generateKeyPair($passphrase);
		$this->writeKeyPair($keyPair, $this->getDeploymentConfigurationPath() . '/Keys/Local.key');
		$this->outputLine('Local key generated');
	}

	/**
	 * Encrypt configuration with the local key
	 *
	 * This command scans the subdirectory of "Build/Deploy/Configuration" for configuration
	 * files that should be encrypted. An optional deployment name restricts this operation to configuration
	 * files of a specific deployment (e.g. "Build/Deploy/Configuration/Staging").
	 *
	 * Only .yaml files with a header of "#!ENCRYPT" are encrypted.
	 *
	 * @param string $deploymentName Optional deployment name to selectively encrypt the configuration
	 * @return void
	 * @see typo3.deploy:encrypt:open
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function sealCommand($deploymentName = '') {
		$keyPair = $this->readKeyPair($this->getDeploymentConfigurationPath() . '/Keys/Local.key');
		$configurations = \TYPO3\FLOW3\Utility\Files::readDirectoryRecursively($this->getDeploymentConfigurationPath() . '/Configuration/' . $deploymentName, 'yaml');
		foreach ($configurations as $configuration) {
			$data = file_get_contents($configuration);
			if (strpos($data, '#!ENCRYPT') !== 0) {
				continue;
			}
			$crypted = $this->encryptionService->encryptData($data, $keyPair->getPublicKey());
			$targetFilename = $configuration . '.encrypted';
			file_put_contents($targetFilename, $crypted);
			unlink($configuration);
			$this->outputLine('Sealed ' . $targetFilename);
		}
	}

	/**
	 * Open encrypted configuration with the local key
	 *
	 * Like the seal command, this can be restricted to a specific deployment. If a passphrase
	 * was used to encrypt the local private key, it must be specified as the passphrase
	 * argument to open the configuration files.
	 *
	 * @param string $passphrase Passphrase to decrypt the local key (if encrypted)
	 * @param string $deploymentName Optional deployment name to selectively decrypt the configuration
	 * @return void
	 * @see typo3.deploy:encrypt:seal
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function openCommand($passphrase = NULL, $deploymentName = '') {
		$keyPair = $this->readKeyPair($this->getDeploymentConfigurationPath() . '/Keys/Local.key');
		try {
			$keyPair = $this->encryptionService->openKeyPair($keyPair, $passphrase);
		} catch(\TYPO3\Deploy\Encryption\InvalidPassphraseException $exception) {
			$this->outputLine('Local key is encrypted with passphrase. Wrong or no passphrase given.');
			$this->quit(1);
		}
		$configurations = \TYPO3\FLOW3\Utility\Files::readDirectoryRecursively($this->getDeploymentConfigurationPath() . '/Configuration/' . $deploymentName, 'yaml.encrypted');
		foreach ($configurations as $configuration) {
			$crypted = file_get_contents($configuration);
			$data = $this->encryptionService->decryptData($crypted, $keyPair->getPrivateKey());
			$targetFilename = substr($configuration, 0, -strlen('.encrypted'));
			file_put_contents($targetFilename, $data);
			unlink($configuration);
			$this->outputLine('Opened ' . $targetFilename);
		}
	}

	/**
	 * Writes a key pair to a file
	 *
	 * @param \TYPO3\Deploy\Encryption\KeyPair $keyPair
	 * @param string $filename
	 * @return void
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	protected function writeKeyPair(\TYPO3\Deploy\Encryption\KeyPair $keyPair, $filename) {
		$data = json_encode(array(
			'encrypted' => $keyPair->isEncrypted(),
			'privateKey' => $keyPair->getPrivateKey(),
			'publicKey' => $keyPair->getPublicKey()
		));
		file_put_contents($filename, $data);
	}

	/**
	 * Reads a key pair from a file
	 *
	 * @param string $filename
	 * @return \TYPO3\Deploy\Encryption\KeyPair
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	protected function readKeyPair($filename) {
		$data = file_get_contents($filename);
		$data = json_decode($data, TRUE);
		$keyPair = new \TYPO3\Deploy\Encryption\KeyPair($data['privateKey'], $data['publicKey'], $data['encrypted']);
		return $keyPair;
	}

	/**
	 * Get the deployment configuration base path
	 *
	 * @return string
	 */
	protected function getDeploymentConfigurationPath() {
		return FLOW3_PATH_ROOT . 'Build/Deploy';
	}

}
?>