<?php
declare(strict_types=1);

namespace Obukhov\GetYourGuideChallenge\Model;

use Assert\Assertion;
use DateTimeImmutable;
use DateTimeInterface;

class Product
{
    const DATE_TIME_FORMAT = 'Y-m-d\TH:i';
    /** @var int */
    protected $placesAvailable;

    /** @var int */
    protected $activityDurationInMinutes;

    /** @var  int */
    protected $productId;

    /** @var  DateTimeImmutable */
    protected $activityStartDatetime;

    /** @var  DateTimeImmutable|null */
    protected $activityEndDatetime;

    public function __construct(
        int $placesAvailable,
        int $activityDurationInMinutes,
        int $productId,
        DateTimeImmutable $activityStartDatetime
    ) {
        $this->placesAvailable = $placesAvailable;
        $this->activityDurationInMinutes = $activityDurationInMinutes;
        $this->productId = $productId;
        $this->activityStartDatetime = $activityStartDatetime;
    }

    public static function createFromArray(array $array)
    {
        Assertion::keyExists($array, 'places_available');
        Assertion::keyExists($array, 'activity_duration_in_minutes');
        Assertion::keyExists($array, 'product_id');
        Assertion::keyExists($array, 'activity_start_datetime');

        Assertion::integer($array['places_available']);
        Assertion::integer($array['activity_duration_in_minutes']);
        Assertion::integer($array['product_id']);
        Assertion::string($array['activity_start_datetime']);

        $activityStartDatetime = \DateTimeImmutable::createFromFormat(
            self::DATE_TIME_FORMAT,
            $array['activity_start_datetime']
        );

        Assertion::isObject(
            $activityStartDatetime,
            sprintf('Wrong format datetime %s', $array['activity_start_datetime'])
        );

        return new self(
            $array['places_available'],
            $array['activity_duration_in_minutes'],
            $array['product_id'],
            $activityStartDatetime
        );
    }

    public function fits(DateTimeInterface $start, DateTimeInterface $end, int $numberOfTravellers)
    {
        if ($numberOfTravellers > $this->placesAvailable) {
            return false;
        }

        if ($start > $this->activityStartDatetime) {
            return false;
        }

        if ($end < $this->getActivityEndDatetime()) {
            return false;
        }

        return true;

    }

    public function getPlacesAvailable(): int
    {
        return $this->placesAvailable;
    }

    public function getActivityDurationInMinutes(): int
    {
        return $this->activityDurationInMinutes;
    }

    public function getProductId(): int
    {
        return $this->productId;
    }

    public function getActivityStartDatetime(): DateTimeImmutable
    {
        return $this->activityStartDatetime;
    }

    public function toArray(): array
    {
        return [
            'places_available' => $this->getPlacesAvailable(),
            'activity_duration_in_minutes' => $this->getActivityDurationInMinutes(),
            'product_id' => $this->getProductId(),
            'activity_start_datetime' => $this->getActivityStartDatetime()->format(self::DATE_TIME_FORMAT),
        ];
    }

    public function getActivityEndDatetime(): DateTimeImmutable
    {
        if (empty($this->activityEndDatetime)) {
            $this->activityEndDatetime = $this->activityStartDatetime->add(
                new \DateInterval(sprintf('PT%dM', $this->activityDurationInMinutes))
            );
        }

        return $this->activityEndDatetime;
    }
}


