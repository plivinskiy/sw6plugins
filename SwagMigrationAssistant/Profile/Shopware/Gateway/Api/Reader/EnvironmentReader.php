<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware\Gateway\Api\Reader;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException as GuzzleRequestException;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Shopware\Core\Framework\ShopwareHttpException;
use SwagMigrationAssistant\Exception\GatewayReadException;
use SwagMigrationAssistant\Exception\InvalidConnectionAuthenticationException;
use SwagMigrationAssistant\Exception\RequestCertificateInvalidException;
use SwagMigrationAssistant\Exception\SslRequiredException;
use SwagMigrationAssistant\Migration\Gateway\Reader\EnvironmentReaderInterface;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Migration\RequestStatusStruct;
use SwagMigrationAssistant\Profile\Shopware\Exception\PluginNotInstalledException;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Connection\ConnectionFactoryInterface;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class EnvironmentReader implements EnvironmentReaderInterface
{
    /**
     * @var Client
     */
    private $client;

    /**
     * @var ConnectionFactoryInterface
     */
    private $connectionFactory;

    public function __construct(ConnectionFactoryInterface $connectionFactory)
    {
        $this->connectionFactory = $connectionFactory;
    }

    public function read(MigrationContextInterface $migrationContext): array
    {
        $this->client = $this->connectionFactory->createApiClient($migrationContext);

        $information = [
            'environmentInformation' => [],
            'requestStatus' => new RequestStatusStruct(),
        ];

        if ($this->doSecureCheck($information)) {
            return $information;
        }

        if (isset($information['requestStatus'])) {
            /** @var RequestStatusStruct $requestStatus */
            $requestStatus = $information['requestStatus'];
            if ($requestStatus->getCode() === (new SslRequiredException())->getErrorCode()) {
                $requestStatus->setIsWarning(false);

                return $information;
            }
        }

        if ($this->doInsecureCheck($information)) {
            return $information;
        }

        if ($this->doShopwareCheck($information)) {
            return $information;
        }

        return $information;
    }

    private function doSecureCheck(array &$information): bool
    {
        try {
            $information['environmentInformation'] = $this->readData($this->client, true);

            return true;
        } catch (ShopwareHttpException $eVerified) {
            $information['requestStatus'] = new RequestStatusStruct($eVerified->getErrorCode(), $eVerified->getMessage(), true);

            return false;
        }
    }

    private function doInsecureCheck(array &$information): bool
    {
        try {
            $information['environmentInformation'] = $this->readData($this->client, false);

            return true;
        } catch (ShopwareHttpException $eUnverified) {
            return false;
        }
    }

    private function doShopwareCheck(array &$information): bool
    {
        try {
            if ($this->checkForShopware($this->client)) {
                throw new PluginNotInstalledException();
            }

            throw new GatewayReadException('Shopware 5.5 Api SwagMigrationEnvironment', 466);
        } catch (ShopwareHttpException $eOther) {
            $information['requestStatus'] = new RequestStatusStruct($eOther->getErrorCode(), $eOther->getMessage());

            return true;
        }
    }

    /**
     * @throws GatewayReadException
     * @throws RequestCertificateInvalidException
     * @throws InvalidConnectionAuthenticationException
     * @throws SslRequiredException
     */
    private function readData(Client $apiClient, bool $verified = false): array
    {
        $config = $apiClient->getConfig();
        $config = array_merge($config, [
            'verify' => $verified,
        ]);

        $result = $this->doSecureRequest(
            $apiClient,
            'SwagMigrationEnvironment',
            $config
        );

        if ($result->getStatusCode() !== SymfonyResponse::HTTP_OK) {
            throw new GatewayReadException('Shopware 5.5 Api SwagMigrationEnvironment', 466);
        }

        $arrayResult = json_decode($result->getBody()->getContents(), true);

        if (!isset($arrayResult['data'])) {
            throw new GatewayReadException('Shopware 5.5 Api SwagMigrationEnvironment', 466);
        }

        return $arrayResult['data'];
    }

    private function checkForShopware(Client $apiClient): bool
    {
        $config = $apiClient->getConfig();
        $config['verify'] = false;

        $result = $this->doSecureRequest(
            $apiClient,
            'version',
            $config
        );

        if ($result->getStatusCode() === SymfonyResponse::HTTP_NOT_FOUND) {
            return false;
        }

        if ($result->getStatusCode() !== SymfonyResponse::HTTP_OK) {
            throw new GatewayReadException('Shopware 5.5 Api SwagMigrationEnvironment', 466);
        }

        $arrayResult = json_decode($result->getBody()->getContents(), true);

        if (!isset($arrayResult['success'])
            || (isset($arrayResult['success']) && $arrayResult['success'] === false)
        ) {
            return false;
        }

        return true;
    }

    /**
     * @throws GatewayReadException
     * @throws InvalidConnectionAuthenticationException
     * @throws RequestCertificateInvalidException
     * @throws SslRequiredException
     */
    private function doSecureRequest(Client $apiClient, string $endpoint, array $config): GuzzleResponse
    {
        try {
            /** @var GuzzleResponse $result */
            $result = $apiClient->get(
                $endpoint,
                $config
            );

            return $result;
        } catch (ClientException $e) {
            if ($e->getCode() === 401) {
                throw new InvalidConnectionAuthenticationException($endpoint);
            }

            throw new GatewayReadException('Shopware 5.5 Api SwagMigrationEnvironment', 466);
        } catch (GuzzleRequestException $e) {
            if ($e->getResponse() !== null && mb_strpos($e->getResponse()->getBody()->getContents(), 'SSL required')) {
                throw new SslRequiredException();
            }

            if (isset($e->getHandlerContext()['errno']) && $e->getHandlerContext()['errno'] === 60) {
                throw new RequestCertificateInvalidException($e->getHandlerContext()['url']);
            }
            throw new GatewayReadException('Shopware 5.5 Api SwagMigrationEnvironment', 466);
        }
    }
}
