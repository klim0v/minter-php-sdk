<?php

namespace Minter\SDK;

use BitWasp\BitcoinLib\BIP39\BIP39;
use kornrunner\Keccak;
use BIP\BIP44;

/**
 * Class MinterWallet
 * @package Minter\SDK
 */
class MinterWallet
{
    /**
     * Amount of entropy bits
     */
    const BIP44_ENTROPY_BITS = 128;

    /**
     * Address path for creating wallet from the seed
     */
    const BIP44_SEED_ADDRESS_PATH = "m/44'/60'/0'/0/0";

    /**
     * Create Minter wallet
     *
     * @return array
     * @throws \Exception
     */
    public static function create(): array
    {
        $entropy = BIP39::generateEntropy(self::BIP44_ENTROPY_BITS);
        $mnemonic = BIP39::entropyToMnemonic($entropy);
        $seed = BIP39::mnemonicToSeedHex($mnemonic, '');
        $privateKey = BIP44::fromMasterSeed($seed)->derive(self::BIP44_SEED_ADDRESS_PATH)->privateKey;

        $publicKey = self::privateToPublic($privateKey);

        $address = self::getAddressFromPublicKey($publicKey);

        return [
            'address' => $address,
            'private_key' => $privateKey,
            'mnemonic' => $mnemonic,
            'seed' => $seed
        ];
    }
    
    /**
     * Generate public key
     *
     * @param string $privateKey
     * @return string
     */
    public static function privateToPublic(string $privateKey): string
    {
        $context = secp256k1_context_create(SECP256K1_CONTEXT_SIGN | SECP256K1_CONTEXT_VERIFY);

        // convert key to binary
        $privateKey = hex2bin($privateKey);

        /** @var $publicKeyResource */
        $publicKeyResource = null;
        secp256k1_ec_pubkey_create($context, $publicKeyResource, $privateKey);

        $publicKey = null;
        secp256k1_ec_pubkey_serialize($context, $publicKeyResource, $publicKey, false);

        return substr(bin2hex($publicKey), 2, 130);
    }

    /**
     * Retrieve address from public key
     *
     * @param string $publicKey
     * @return string
     * @throws \Exception
     */
    public static function getAddressFromPublicKey(string $publicKey): string
    {
        $hash = Keccak::hash(hex2bin($publicKey), 256);

        return MinterPrefix::ADDRESS . substr($hash, -40);
    }

    /**
     * Validate that address is valid Minter address
     *
     * @param string $address
     * @return bool
     */
    public static function validateAddress(string $address): bool
    {
        return strlen($address) === 42 && substr($address, 0, 2) === MinterPrefix::ADDRESS && ctype_xdigit(substr($address, -40));
    }
}