<?php
declare(ENCODING = 'utf-8');
namespace TYPO3\Deploy\Encryption;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3.Deploy".               *
 *                                                                        *
 *                                                                        */

use Doctrine\ORM\Mapping as ORM;
use TYPO3\FLOW3\Annotations as FLOW3;

/**
 * OpenSSL encryption service
 *
 * An encryption service for key generation and encryption / decryption of data
 * using the OpenSSL library.
 *
 * @FLOW3\Scope("singleton")
 */
class OpenSslEncryptionService implements EncryptionServiceInterface {

	/**
	 * Generate a key pair (public / private key) with optional passphrase
	 * that protects the private key.
	 *
	 * @param string $passphrase
	 * @return \TYPO3\Deploy\Encryption\KeyPair
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function generateKeyPair($passphrase = NULL) {
		$privateKey = NULL;
		$encrypted = $passphrase !== NULL;
		$keyPair = openssl_pkey_new();
		openssl_pkey_export($keyPair, $privateKey, $passphrase);
		$keyDetails = openssl_pkey_get_details($keyPair);
		$publicKey = $keyDetails['key'];
		openssl_pkey_free($keyPair);

		return new \TYPO3\Deploy\Encryption\KeyPair($privateKey, $publicKey, $encrypted);
	}

	/**
	 * Open (decrypt) a protected key pair
	 *
	 * @param \TYPO3\Deploy\Encryption\KeyPair $keyPair
	 * @param string $passphrase
	 * @return \TYPO3\Deploy\Encryption\KeyPair
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function openKeyPair(\TYPO3\Deploy\Encryption\KeyPair $keyPair, $passphrase) {
		return $this->exportKeyPair($keyPair, $passphrase);
	}

	/**
	 * Change the passphrase of a protected key pair
	 *
	 * @param \TYPO3\Deploy\Encryption\KeyPair $keyPair
	 * @param string $oldPassphrase
	 * @param string $newPassphrase
	 * @return \TYPO3\Deploy\Encryption\KeyPair
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function changePassphrase($keyPair, $oldPassphrase, $newPassphrase) {
		if (empty($newPassphrase)) {
			throw new \InvalidArgumentException('New passphrase must not be empty', 1300101668);
		}
		return $this->exportKeyPair($keyPair, $oldPassphrase, $newPassphrase);
	}

	/**
	 *
	 * @param string $data
	 * @param string $publicKey
	 * @return string
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function encryptData($data, $publicKey) {
		$cryptedData = NULL;
		$envelopeKeys = NULL;
		openssl_seal($data, $cryptedData, $envelopeKeys, array($publicKey));
		$envelopeKey = $envelopeKeys[0];
		$crypted = base64_encode($envelopeKey) . ':' . base64_encode($cryptedData);
		return $crypted;
	}

	/**
	 *
	 * @param string $data
	 * @param string $privateKey
	 * @return string
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function decryptData($data, $privateKey) {
		if (!is_string($privateKey)) throw new \InvalidArgumentException('Private key should be given as string', 1300211696);

		list($envelopeKey, $cryptedData) = explode(':', $data, 2);
		$envelopeKey = base64_decode($envelopeKey);
		$cryptedData = base64_decode($cryptedData);
		openssl_open($cryptedData, $decrypted, $envelopeKey, $privateKey);
		return $decrypted;
	}

	/**
	 * Re-export the private key to change or disable the passphrase
	 *
	 * @param \TYPO3\Deploy\Encryption\KeyPair $keyPair
	 * @param string $passphrase Passphrase for opening the key pair
	 * @param string $exportPassphrase Passphrase for the exported key pair (NULL for unencrypted private key)
	 * @return \TYPO3\Deploy\Encryption\KeyPair
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	protected function exportKeyPair($keyPair, $passphrase, $exportPassphrase = NULL) {
		$privateKey = NULL;
		$encrypted = $exportPassphrase !== NULL;
		$key = openssl_pkey_get_private($keyPair->getPrivateKey(), $passphrase);
		if ($key === FALSE) {
			throw new \TYPO3\Deploy\Encryption\InvalidPassphraseException('Invalid passphrase, could not open key', 1300101137);
		}
		openssl_pkey_export($key, $privateKey, $exportPassphrase);
		openssl_free_key($key);
		return new \TYPO3\Deploy\Encryption\KeyPair($privateKey, $keyPair->getPublicKey(), $encrypted);
	}

}
?>