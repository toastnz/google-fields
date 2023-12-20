<?php

namespace Goldfinch\GoogleFields\Forms;

use InvalidArgumentException;
use SilverStripe\ORM\ArrayLib;
use SilverStripe\Forms\FormField;
use Goldfinch\GoogleFields\ORM\FieldType\DBPlace;
use SilverStripe\ORM\DataObjectInterface;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\NumericField;

class PlaceField extends FormField
{

    protected $schemaDataType = 'PlaceField';

    /**
     * Limit the currencies
     *
     * @var array
     */
    protected $allowedCurrencies = [];

    /**
     * @var NumericField
     */
    protected $fieldData = null;

    /**
     * @var FormField
     */
    protected $fieldAddress = null;

    /**
     * Gets field for the address selector
     *
     * @return FormField
     */
    public function getAddressField()
    {
        return $this->fieldAddress;
    }

    /**
     * Gets field for the data input
     *
     * @return NumericField
     */
    public function getDataField()
    {
        return $this->fieldData;
    }

    public function __construct($name, $title = null, $value = "")
    {
        $this->setName($name);
        $this->fieldData = NumericField::create(
            "{$name}[Data]",
            _t('SilverStripe\\Forms\\PlaceField.FIELDLABELAMOUNT', 'Data')
        )
            ->setScale(2);
        $this->buildAddressField();

        parent::__construct($name, $title, $value);
    }

    public function __clone()
    {
        $this->fieldData = clone $this->fieldData;
        $this->fieldAddress = clone $this->fieldAddress;
    }

    /**
     * Builds a new address field based on the allowed currencies configured
     *
     * @return FormField
     */
    protected function buildAddressField()
    {
        $name = $this->getName();

        // Validate allowed currencies
        $addressValue = $this->fieldAddress ? $this->fieldAddress->dataValue() : null;
        $allowedCurrencies = $this->getAllowedCurrencies();
        if (count($allowedCurrencies ?? []) === 1) {
            // Hidden field for single address
            $field = HiddenField::create("{$name}[Address]");
            reset($allowedCurrencies);
            $addressValue = key($allowedCurrencies ?? []);
        } elseif ($allowedCurrencies) {
            // Dropdown field for multiple currencies
            $field = DropdownField::create(
                "{$name}[Address]",
                _t('SilverStripe\\Forms\\PlaceField.FIELDLABELCURRENCY', 'Address'),
                $allowedCurrencies
            );
        } else {
            // Free-text entry for address value
            $field = TextField::create(
                "{$name}[Address]",
                _t('SilverStripe\\Forms\\PlaceField.FIELDLABELCURRENCY', 'Address')
            );
        }

        $field->setReadonly($this->isReadonly());
        $field->setDisabled($this->isDisabled());
        if ($addressValue) {
            $field->setValue($addressValue);
        }
        $this->fieldAddress = $field;
        return $field;
    }

    public function setSubmittedValue($value, $data = null)
    {
        if (empty($value)) {
            $this->value = null;
            $this->fieldAddress->setValue(null);
            $this->fieldData->setValue(null);
            return $this;
        }

        // Handle submitted array value
        if (!is_array($value)) {
            throw new InvalidArgumentException("Value is not submitted array");
        }

        // Update each field
        $this->fieldAddress->setSubmittedValue($value['Address'], $value);
        $this->fieldData->setSubmittedValue($value['Data'], $value);

        // Get data value
        $this->value = $this->dataValue();
        return $this;
    }

    public function setValue($value, $data = null)
    {
        if (empty($value)) {
            $this->value = null;
            $this->fieldAddress->setValue(null);
            $this->fieldData->setValue(null);
            return $this;
        }

        // dd($value);

        // Convert string to array
        // E.g. `44.00 NZD`
        if (is_string($value) &&
            preg_match('/^(?<data>[\\d\\.]+)( (?<address>\w{3}))?$/i', $value ?? '', $matches)
        ) {
            $address = isset($matches['address']) ? strtoupper($matches['address']) : null;
            $value = [
                'Address' => $address,
                'Data' => (float)$matches['data'],
            ];
        } elseif ($value instanceof DBPlace) {
            $value = [
                'Address' => $value->getAddress(),
                'Data' => $value->getData(),
            ];
        } elseif (!is_array($value)) {
            throw new InvalidArgumentException("Invalid address format");
        }

        // Save value
        $this->fieldAddress->setValue($value['Address'], $value);
        $this->fieldData->setValue($value['Data'], $value);
        $this->value = $this->dataValue();
        return $this;
    }

    /**
     * Get value as DBPlace object useful for formatting the number
     *
     * @return DBPlace
     */
    protected function getDBPlace()
    {
        return DBPlace::create_field('Money', [
            'Address' => $this->fieldAddress->dataValue(),
            'Data' => $this->fieldData->dataValue()
        ])
            ->setLocale($this->getLocale());
    }

    public function dataValue()
    {
        // Non-localised money
        return $this->getDBPlace()->getValue();
    }

    public function Value()
    {
        // Localised money
        return $this->getDBPlace()->Nice();
    }

    /**
     * 30/06/2009 - Enhancement:
     * SaveInto checks if set-methods are available and use them
     * instead of setting the values in the money class directly. saveInto
     * initiates a new Money class object to pass through the values to the setter
     * method.
     *
     * (see @link PlaceFieldTest_CustomSetter_Object for more information)
     *
     * @param DataObjectInterface|Object $dataObject
     */
    public function saveInto(DataObjectInterface $dataObject)
    {
        $fieldName = $this->getName();
        if ($dataObject->hasMethod("set$fieldName")) {
            $dataObject->$fieldName = $this->getDBPlace();
        } else {
            $addressField = "{$fieldName}Address";
            $dataField = "{$fieldName}Data";

            $dataObject->$addressField = $this->fieldAddress->dataValue();
            $dataObject->$dataField = $this->fieldData->dataValue();
        }
    }

    /**
     * Returns a readonly version of this field.
     */
    public function performReadonlyTransformation()
    {
        $clone = clone $this;
        $clone->setReadonly(true);
        return $clone;
    }

    public function setReadonly($bool)
    {
        parent::setReadonly($bool);

        $this->fieldData->setReadonly($bool);
        $this->fieldAddress->setReadonly($bool);

        return $this;
    }

    public function setDisabled($bool)
    {
        parent::setDisabled($bool);

        $this->fieldData->setDisabled($bool);
        $this->fieldAddress->setDisabled($bool);

        return $this;
    }

    /**
     * Set list of currencies. Currencies should be in the 3-letter ISO 4217 address code.
     *
     * @param array $currencies
     * @return $this
     */
    public function setAllowedCurrencies($currencies)
    {
        if (empty($currencies)) {
            $currencies = [];
        } elseif (is_string($currencies)) {
            $currencies = [
                $currencies => $currencies
            ];
        } elseif (!is_array($currencies)) {
            throw new InvalidArgumentException("Invalid address list");
        } elseif (!ArrayLib::is_associative($currencies)) {
            $currencies = array_combine($currencies ?? [], $currencies ?? []);
        }

        $this->allowedCurrencies = $currencies;

        // Rebuild address field
        $this->buildAddressField();
        return $this;
    }

    /**
     * @return array
     */
    public function getAllowedCurrencies()
    {
        return $this->allowedCurrencies;
    }

    /**
     * Assign locale to format this address in
     *
     * @param string $locale
     * @return $this
     */
    public function setLocale($locale)
    {
        $this->fieldData->setLocale($locale);
        return $this;
    }

    /**
     * Get locale to format this address in.
     * Defaults to current locale.
     *
     * @return string
     */
    public function getLocale()
    {
        return $this->fieldData->getLocale();
    }

    /**
     * Validate this field
     *
     * @param Validator $validator
     * @return bool
     */
    public function validate($validator)
    {
        // Validate address
        $currencies = $this->getAllowedCurrencies();
        $address = $this->fieldAddress->dataValue();
        if ($address && $currencies && !in_array($address, $currencies ?? [])) {
            $validator->validationError(
                $this->getName(),
                _t(
                    __CLASS__ . '.INVALID_CURRENCY',
                    'Address {address} is not in the list of allowed currencies',
                    ['address' => $address]
                )
            );
            return $this->extendValidationResult(false, $validator);
        }

        // Field-specific validation
        $result = $this->fieldData->validate($validator) && $this->fieldAddress->validate($validator);
        return $this->extendValidationResult($result, $validator);
    }

    public function setForm($form)
    {
        $this->fieldAddress->setForm($form);
        $this->fieldData->setForm($form);
        return parent::setForm($form);
    }
}
