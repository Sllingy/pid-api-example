<?php

declare(strict_types=1);

namespace App\API\PointsOfSale;

use App\Repository\OpeningHoursRepository;
use App\Repository\PointOfSaleRepository;
use GuzzleHttp\Client;
use InvalidArgumentException;
use Nette\Utils\Json;
use Nette\Utils\JsonException;
use Nette\Application\UI\Presenter;
use Nette\Database\ConnectionException;
use Nette\Database\DriverException;

/**
 * Presenter for handling Points of Sale data actions.
 */
final class PointsOfSalePresenter extends Presenter
{
    public function __construct(
        private PointOfSaleRepository $pointOfSaleRepository,
        private OpeningHoursRepository $openingHoursRepository,
    ) {
    }

    /**
     * Lists all formatted Points of Sale filtered by the day and time parameters.
     */
    public function actionList(): void
    {
        $day = $this->getParameter('day');
        if ($day !== null) {
            $day = (int) $day;
        }

        $time = $this->getParameter('time');
        if ($time !== null) {
            $time = (string) $time;
        }

        try {
            $pointsOfSale = $this->pointOfSaleRepository->getAllFormatted($day, $time);
        } catch (InvalidArgumentException $e) {
            $this->sendJson([
                'code' => 400,
                'message' => 'Invalid parameter provided - ' . $e->getMessage(),
            ]);
        } catch (ConnectionException | DriverException $e) {
            $this->sendJson([
                'code' => 500,
                'message' => 'Failed to load data from the database - ' . $e->getMessage(),
            ]);
        }

        $this->sendJson([
            'code' => 200,
            'data' => $pointsOfSale,
        ]);
    }

    /**
     * Retrieves and parses data from an external PID API.
     *
     * @return array Parsed PID data
     */
    private function getPidData(): array
    {
        $client = new Client();
        $result = $client->get('https://data.pid.cz/pointsOfSale/json/pointsOfSale.json');

        if ($result->getStatusCode() !== 200) {
            $this->sendJson([
                'code' => 500,
                'message' => 'Failed to retrieve data from PID',
            ]);
        }

        try {
            return Json::decode($result->getBody()->getContents(), true);
        } catch (JsonException) {
            $this->sendJson([
                'code' => 500,
                'message' => 'Failed to parse PID JSON',
            ]);
        }
    }

    /**
     * Clears all Points of Sale and Opening Hours data.
     */
    private function clearData(): void
    {
        try {
            $this->openingHoursRepository->clearAllData();
            $this->pointOfSaleRepository->clearAllData();
        } catch (ConnectionException | DriverException $e) {
            $this->sendJson([
                'code' => 500,
                'message' => 'Failed to clear the database tables - ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Processes Point of Sale and Opening Hours data and saves it to the database.
     *
     * @param array $data Point of Sale and Opening Hours data
     */
    private function processPidData(array $data): void
    {
        foreach ($data as $entry) {
            $this->pointOfSaleRepository->insertData([
                'id' => $entry['id'],
                'type' => $entry['type'],
                'name' => $entry['name'],
                'address' => isset($entry['address']) ? $entry['address'] : null, // Optional
                'lat' => $entry['lat'],
                'lon' => $entry['lon'],
                'services' => $entry['services'],
                'pay_methods' => $entry['payMethods'],
                'remarks' => isset($entry['remarks']) ? $entry['remarks'] : null, // Optional
                'link' => isset($entry['link']) ? $entry['link'] : null // Optional
            ]);

            foreach ($entry['openingHours'] as $openingHours) {
                $allHours = explode(',', $openingHours['hours']);

                foreach ($allHours as $individualHours) {
                    $individualHours = str_replace('–', '-', $individualHours); // Some hours contain a different separating character
                    $times = explode('-', $individualHours);

                    $this->openingHoursRepository->insertData([
                        'point_of_sale_id' => $entry['id'],
                        'day_from' => $openingHours['from'],
                        'day_to' => $openingHours['to'],
                        'open_time' => str_pad($times[0], 2, '0', STR_PAD_LEFT), // Add zeroes if missing
                        'close_time' => str_pad($times[1], 2, '0', STR_PAD_LEFT), // Add zeroes if missing
                    ]);
                }
            }
        }
    }

    /**
     * Updates the Points of Sale and Opening Hours data from the PID API.
     */
    public function actionUpdate(): void
    {
        $this->clearData();

        $pidData = $this->getPidData();

        try {
            $this->processPidData($pidData);
        } catch (ConnectionException | DriverException $e) {
            $this->sendJson([
                'code' => 500,
                'message' => 'Failed to save data to the database - ' . $e->getMessage(),
            ]);
        }

        $this->sendJson([
            'code' => 200,
            'message' => 'PID data successfully updated',
        ]);
    }
}
