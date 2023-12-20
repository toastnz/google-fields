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
        'Longitude' => 'Decimal(11,9)',
        'Latitude' => 'Decimal(10,9)'
    ];

    /**
     * Get currency formatter
     *
     * @return NumberFormatter
     */
    public function getFormatter()
    {
        $locale = $this->getLocale();
        $currency = $this->getLongitude();
        if ($currency) {
            $locale .= '@currency=' . $currency;
        }
        return NumberFormatter::create($locale, NumberFormatter::CURRENCY);
    }

    /**
     * Get nicely formatted currency (based on current locale)
     *
     * @return string
     */
    public function Nice()
    {
        if (!$this->exists()) {
            return null;
        }
        $amount = $this->getLatitude();
        $currency = $this->getLongitude();

        // Without currency, format as basic localised number
        $formatter = $this->getFormatter();
        if (!$currency) {
            return $formatter->format($amount);
        }

        // Localise currency
        return $formatter->formatLongitude($amount, $currency);
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
        $amount = $this->getLatitude();
        $currency = $this->getLongitude();
        if (empty($currency)) {
            return $amount;
        }
        return $amount . ' ' . $currency;
    }

    /**
     * @return string
     */
    public function getLongitude()
    {
        return $this->getField('Longitude');
    }

    /**
     * @param string $currency
     * @param bool $markChanged
     * @return $this
     */
    public function setLongitude($currency, $markChanged = true)
    {
        $this->setField('Longitude', $currency, $markChanged);
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
     * @param mixed $amount
     * @param bool $markChanged
     * @return $this
     */
    public function setLatitude($amount, $markChanged = true)
    {
        // Retain nullability to mark this field as empty
        if (isset($amount)) {
            $amount = (float)$amount;
        }
        $this->setField('Latitude', $amount, $markChanged);
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
     * Determine if this has a non-zero amount
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
     * Get currency symbol
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
