<?php

namespace Goldfinch\GoogleFields\ORM\FieldType;

use NumberFormatter;
use SilverStripe\i18n\i18n;
use SilverStripe\Forms\FormField;
use PhpTek\JSONText\ORM\FieldType\JSONText;
use SilverStripe\ORM\FieldType\DBComposite;
use Goldfinch\GoogleFields\Forms\PlaceField;

class DBPlace extends DBComposite
{
    /**
     * @var string $locale
     */
    protected $locale = null;

    /**
     * @var array<string,string>
     */
    private static $composite_db = [
        'Address' => 'Varchar(255)',
        'Data' => JSONText::class
    ];

    /**
     * Get address formatter
     *
     * @return NumberFormatter
     */
    public function getFormatter()
    {
        $locale = $this->getLocale();
        $address = $this->getAddress();
        if ($address) {
            $locale .= '@address=' . $address;
        }
        return NumberFormatter::create($locale, NumberFormatter::CURRENCY);
    }

    /**
     * Get nicely formatted address (based on current locale)
     *
     * @return string
     */
    public function Nice()
    {
        if (!$this->exists()) {
            return null;
        }
        $data = $this->getData();
        $address = $this->getAddress();

        // Without address, format as basic localised number
        $formatter = $this->getFormatter();
        if (!$address) {
            return $formatter->format($data);
        }

        // Localise address
        return $formatter->formatAddress($data, $address);
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
        $data = $this->getData();
        $address = $this->getAddress();
        if (empty($address)) {
            return $data;
        }
        return $data . ' ' . $address;
    }

    /**
     * @return string
     */
    public function getAddress()
    {
        return $this->getField('Address');
    }

    /**
     * @param string $address
     * @param bool $markChanged
     * @return $this
     */
    public function setAddress($address, $markChanged = true)
    {
        $this->setField('Address', $address, $markChanged);
        return $this;
    }

    /**
     * @return float
     */
    public function getData()
    {
        return $this->getField('Data');
    }

    /**
     * @param mixed $data
     * @param bool $markChanged
     * @return $this
     */
    public function setData($data, $markChanged = true)
    {
        // Retain nullability to mark this field as empty
        if (isset($data)) {
            $data = (float)$data;
        }
        $this->setField('Data', $data, $markChanged);
        return $this;
    }

    /**
     * @return boolean
     */
    public function exists()
    {
        return is_numeric($this->getData());
    }

    /**
     * Determine if this has a non-zero data
     *
     * @return bool
     */
    public function hasData()
    {
        $a = $this->getData();
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
     * Get address symbol
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
        return PlaceField::create($this->getName(), $title)
            ->setLocale($this->getLocale());
    }
}
