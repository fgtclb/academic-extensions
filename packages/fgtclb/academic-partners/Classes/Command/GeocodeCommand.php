<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPartners\Command;

use FGTCLB\AcademicPartners\Domain\Repository\PartnerRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Utility\HttpUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;

class GeocodeCommand extends Command
{
    // Base URL to fetch latitude and longitude of a partner string
    protected string $geocodingUrl = 'https://nominatim.openstreetmap.org/search?format=json&limit=5';

    public function __construct(
        private readonly PartnerRepository $partnerRepository,
        private readonly PersistenceManager $persistenceManager,
        private readonly RequestFactory $requestFactory,
        private readonly ?LoggerInterface $logger = null,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setHelp('Geocode partner addresses using the Nominatim API')
            ->addArgument(
                'referrer',
                InputArgument::REQUIRED,
                'HTTP referer to identify app in Nominatim\'s API',
            );
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $referrer = $input->getArgument('referrer');

        $partner = $this->partnerRepository->findNextForGeolocation();

        // Nothing to geocode
        if (!$partner) {
            return Command::SUCCESS;
        }

        if ($partner->getAddressStreet() === ''
            || $partner->getAddressStreetNumber() === ''
            || $partner->getAddressCity() === ''
            || $partner->getAddressZip() === ''
            || $partner->getAddressCountry() === ''
        ) {
            $partner->setGeocodeStatus('failed');
            $partner->setGeocodeMessage('There are not sufficient address details given.');
            $this->partnerRepository->update($partner);
            $this->persistenceManager->persistAll();
            return Command::SUCCESS;
        }

        $now = new \DateTime();
        $partner->setGeocodeLastRun($now);

        $address = [];
        $address['street'] = trim(implode(' ', [$partner->getAddressStreet(), $partner->getAddressStreetNumber()]));
        $address['city'] = $partner->getAddressCity();
        $address['postalcode'] = $partner->getAddressZip();
        $address['country'] = $partner->getAddressCountry();
        $addressQuery = HttpUtility::buildQueryString($address, '&', true);

        $url = $this->geocodingUrl . $addressQuery;

        // Add valid HTTP referer to identify app as required by Nominatim's usage policy
        $additionalOptions = [
            'headers' => [
                'Referrer' => $referrer,
            ],
        ];

        try {
            $response = $this->requestFactory->request($url, 'GET', $additionalOptions);
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->warning(
                    'Request to Nominatim API returned status "{statusCode}".',
                    [
                        'request' => $url,
                        'statusCode' => $e->getCode(),
                        'errorMessage' => $e->getMessage(),
                    ]
                );
            }
            return Command::FAILURE;
        }

        $content = $response->getBody()->getContents();
        $geodata = json_decode($content, true);

        if (isset($geodata[0]) && !empty($geodata[0])) {
            $partner->setGeocodeLatitude((float)($geodata[0]['lat']));
            $partner->setGeocodeLongitude((float)($geodata[0]['lon']));
            $partner->setGeocodeStatus('successful');
        } else {
            $partner->setGeocodeStatus('failed');
            $partner->setGeocodeMessage('The address details were not sufficient for geolocalization.');
        }

        $this->partnerRepository->update($partner);
        $this->persistenceManager->persistAll();

        return Command::SUCCESS;
    }
}
