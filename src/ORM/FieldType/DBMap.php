<?php

namespace Goldfinch\GoogleFields\ORM\FieldType;

use NumberFormatter;
use SilverStripe\Forms\FormField;
use Goldfinch\GoogleFields\Forms\MapField;
use SilverStripe\i18n\i18n;
use SilverStripe\ORM\FieldType\DBComposite;

class DBMap extends DBComposite
{
    /**
     * @var string $locale
     */
    protected $locale = null;

    /**
     * @var array<string,string>
     */
    private static $composite_db = [
        'Longitude' => 'Varchar(32)',
        'Latitude' => 'Varchar(32)',
        'Zoom' => 'Varchar(6)'
    ];

    /**
     * Get longitude formatter
     *
     * @return NumberFormatter
     */
    public function getFormatter()
    {
        $locale = $this->getLocale();
        $longitude = $this->getLongitude();
        if ($longitude) {
            $locale .= '@longitude=' . $longitude;
        }
        return NumberFormatter::create($locale, NumberFormatter::CURRENCY);
    }

    /**
     * Get nicely formatted longitude (based on current locale)
     *
     * @return string
     */
    public function Nice()
    {
        if (!$this->exists()) {
            return null;
        }
        $latitude = $this->getLatitude();
        $longitude = $this->getLongitude();

        // Without longitude, format as basic localised number
        $formatter = $this->getFormatter();
        if (!$longitude) {
            return $formatter->format($latitude);
        }

        // Localise longitude
        return $formatter->formatLongitude($latitude, $longitude);
    }

    /**
     * Standard '0.00 CUR' format (non-localised)
     *
     * @return string
     */
    public function getValue()
    {
        if (!$this->exists()) {
            return null;
        }
        $latitude = $this->getLatitude();
        $longitude = $this->getLongitude();
        if (empty($longitude)) {
            return $latitude;
        }
        return $latitude . ' ' . $longitude;
    }

    /**
     * @return string
     */
    public function getLongitude()
    {
        return $this->getField('Longitude');
    }

    /**
     * @param string $longitude
     * @param bool $markChanged
     * @return $this
     */
    public function setLongitude($longitude, $markChanged = true)
    {
        $this->setField('Longitude', $longitude, $markChanged);
        return $this;
    }

    /**
     * @return float
     */
    public function getLatitude()
    {
        return $this->getField('Latitude');
    }

    /**
     * @return float
     */
    public function getZoom()
    {
        return $this->getField('Zoom');
    }

    /**
     * @param mixed $latitude
     * @param bool $markChanged
     * @return $this
     */
    public function setLatitude($latitude, $markChanged = true)
    {
        // Retain nullability to mark this field as empty
        if (isset($latitude)) {
            $latitude = (float)$latitude;
        }
        $this->setField('Latitude', $latitude, $markChanged);
        return $this;
    }

    /**
     * @param mixed $latitude
     * @param bool $markChanged
     * @return $this
     */
    public function setZoom($zoom, $markChanged = true)
    {
        // Retain nullability to mark this field as empty
        if (isset($zoom)) {
            $zoom = (float)$zoom;
        }
        $this->setField('Zoom', $zoom, $markChanged);
        return $this;
    }

    /**
     * @return boolean
     */
    public function exists()
    {
        return is_numeric($this->getLatitude());
    }

    /**
     * Determine if this has a non-zero latitude
     *
     * @return bool
     */
    public function hasLatitude()
    {
        $a = $this->getLatitude();
        return (!empty($a) && is_numeric($a));
    }

    /**
     * @param string $locale
     * @return $this
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;
        return $this;
    }

    /**
     * @return string
     */
    public function getLocale()
    {
        return $this->locale ?: i18n::get_locale();
    }

    /**
     * Get longitude symbol
     *
     * @return string
     */
    public function getSymbol()
    {
        return $this->getFormatter()->getSymbol(NumberFormatter::CURRENCY_SYMBOL);
    }

    /**
     * Returns a CompositeField instance used as a default
     * for form scaffolding.
     *
     * Used by {@link SearchContext}, {@link ModelAdmin}, {@link DataObject::scaffoldFormFields()}
     *
     * @param string $title Optional. Localized title of the generated instance
     * @param array $params
     * @return FormField
     */
    public function scaffoldFormField($title = null, $params = null)
    {
        return MapField::create($this->getName(), $title)
            ->setLocale($this->getLocale());
    }
}
