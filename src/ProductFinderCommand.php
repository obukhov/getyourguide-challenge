<?php
declare(strict_types=1);

namespace Obukhov\GetYourGuideChallenge;

use Assert\Assertion;
use GuzzleHttp\Client;
use Obukhov\GetYourGuideChallenge\Model\Product;
use SebastianBergmann\GlobalState\RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ProductFinderCommand extends Command
{
    const DATE_TIME_FORMAT = Product::DATE_TIME_FORMAT;

    protected function configure()
    {
        $this
            ->setName('product-find')
            ->addArgument(
                'URL',
                InputArgument::OPTIONAL,
                'Endpoint to retrieve data',
                'http://www.mocky.io/v2/58ff37f2110000070cf5ff16'
            )
            ->addArgument(
                'startTime',
                InputArgument::OPTIONAL,
                sprintf('DateTime to end %s (if missing: now - 1 month)', self::DATE_TIME_FORMAT)
            )
            ->addArgument(
                'endTime',
                InputArgument::OPTIONAL,
                sprintf('DateTime to start %s (if missing: now)', self::DATE_TIME_FORMAT)
            )
            ->addArgument('numberOfTravellers', InputArgument::OPTIONAL, 'Number of travellers', '1');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        list($url, $endDate, $startDate, $numberOfTravellers) = $this->parseAndValidateInput($input);
        $body = $this->getProductList($url);

        $fitProducts = [];
        foreach ($body['product_availabilities'] as $productArray) {
            $product = Product::createFromArray($productArray);
            if ($product->fits($startDate, $endDate, $numberOfTravellers)) {
                $fitProducts[] = $product;
            }
        }

        $this->sortProducts($fitProducts);

        $output->writeln(json_encode($this->formatOutputJson($fitProducts), JSON_PRETTY_PRINT));
    }

    /**
     * @param InputInterface $input
     * @return array
     */
    protected function parseAndValidateInput(InputInterface $input): array
    {
        $url = $input->getArgument('URL');
        Assertion::url($url);

        $endDateString = $input->getArgument('endTime');
        if (empty($endDateString)) {
            $endDate = new \DateTimeImmutable();
        } else {
            $endDate = \DateTimeImmutable::createFromFormat(self::DATE_TIME_FORMAT, $endDateString);
            Assertion::isObject($endDate, 'endTime has wrong format');
        }

        $startDateString = $input->getArgument('startTime');
        if (empty($startDateString)) {
            $startDate = (new \DateTimeImmutable())->sub(new \DateInterval('P30D'));
        } else {
            $startDate = \DateTimeImmutable::createFromFormat(self::DATE_TIME_FORMAT, $startDateString);
            Assertion::isObject($endDate, 'startTime has wrong format');
        }


        $numberOfTravellers = (int)$input->getArgument('numberOfTravellers');
        Assertion::greaterOrEqualThan($numberOfTravellers, 1);

        return array($url, $endDate, $startDate, $numberOfTravellers);
    }

    /**
     * @param string $url
     * @return array
     */
    protected function getProductList(string $url): array
    {
        $client = new Client();
        $response = $client->get($url);

        if ($response->getStatusCode() !== 200) {
            throw new RuntimeException(sprintf('Response code is not expected: %d ', $response->getStatusCode()));
        }

        $body = json_decode((string)$response->getBody(), true);
        Assertion::isArray($body, 'Error parsing response: '.json_last_error_msg());

        return $body;
    }

    /**
     * @param array $fitProducts
     */
    protected function sortProducts(array &$fitProducts)
    {
        usort($fitProducts, function (Product $a, Product $b) {
            if ($a->getProductId() > $b->getProductId()) {
                return 1;
            } elseif ($a->getProductId() < $b->getProductId()) {
                return -1;
            } elseif ($a->getActivityStartDatetime() > $b->getActivityStartDatetime()) {
                return 1;
            } else {
                return -1;
            }
        });
    }

    /**
     * @param $fitProducts
     * @return array
     */
    protected function formatOutputJson($fitProducts): array
    {
        $outputJson = [];

        /** @var Product $product */
        foreach ($fitProducts as $product) {
            if (!array_key_exists($product->getProductId(), $outputJson)) {
                $outputJson[$product->getProductId()] = [
                    'product_id' => $product->getProductId(),
                    'available_starttimes' => [],
                ];
            }

            $outputJson[$product->getProductId()]['available_starttimes'][] = [
                'start_time' => $product->getActivityStartDatetime()->format(self::DATE_TIME_FORMAT),
                'number_of_participants' => 99 - $product->getPlacesAvailable(),
            ];
        }

        return array_values($outputJson);
    }
}
