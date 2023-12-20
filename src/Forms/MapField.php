<?php

namespace Goldfinch\GoogleFields\Forms;

use InvalidArgumentException;
use SilverStripe\ORM\ArrayLib;
use SilverStripe\Forms\FormField;
use SilverStripe\Forms\TextField;
use SilverStripe\Core\Environment;
use SilverStripe\View\Requirements;
use SilverStripe\Forms\LiteralField;
use SilverStripe\ORM\DataObjectInterface;
use Goldfinch\GoogleFields\ORM\FieldType\DBMap;

class MapField extends FormField
{

    protected $schemaDataType = 'MapField';

    protected $settings = [
      'zoom' => 2,
      'lat' => 9.058948064030377,
      'lng' => 82.47341002295963,
    ];

    /**
     * Limit the currencies
     *
     * @var array
     */
    protected $allowedCurrencies = [];

    /**
     * @var TextField
     */
    protected $fieldLatitude = null;

    /**
     * @var FormField
     */
    protected $fieldLongitude = null;

    /**
     * Gets field for the longitude selector
     *
     * @return FormField
     */
    public function getLongitudeField()
    {
        return $this->fieldLongitude;
    }

    /**
     * Gets field for the latitude input
     *
     * @return TextField
     */
    public function getLatitudeField()
    {
        return $this->fieldLatitude;
    }

    /**
     * Gets field for the latitude input
     *
     * @return TextField
     */
    public function getZoomField()
    {
        return $this->fieldZoom;
    }

    public function getMapField()
    {
        return LiteralField::create($this->getName() . 'Map', '<div class="ggm__frame" data-goldfinch-map="frame" data-settings="'.str_replace('"','&quot;', json_encode($this->getSettings())).'"></div>');
    }

    public function getSearchField()
    {
        return TextField::create($this->getName() . 'Search', '')->setAttribute('placeholder', 'Find a place (type and hit enter)')->setAttribute('data-goldfinch-map', 'search')->addExtraClass('ggm__search');
    }

    public function setSettings($settings)
    {
        $this->settings = $settings;

        return $this;
    }

    public function getSettings()
    {
        return $this->settings;
    }

    public function __construct($name, $title = null, $value = "")
    {
        $this->setName($name);

        $this->fieldLatitude = TextField::create(
            "{$name}[Latitude]",
            _t('SilverStripe\\Forms\\MapField.FIELDLABELAMOUNT', 'Latitude')
        );

        $this->fieldLatitude->setAttribute('data-goldfinch-map', 'latitude');

        $this->fieldZoom = TextField::create(
            "{$name}[Zoom]",
            _t('SilverStripe\\Forms\\MapField.FIELDLABELAMOUNT', 'Zoom')
        );

        $this->fieldZoom->setAttribute('data-goldfinch-map', 'zoom');

        $this->buildLongitudeField();

        Requirements::css('goldfinch/google-fields:client/dist/google-fields-style.css');
        Requirements::javascript('goldfinch/google-fields:client/dist/google-fields.js');
        Requirements::javascript('//maps.googleapis.com/maps/api/js?key=' . Environment::getEnv('APP_GOOGLE_MAPS_KEY') . '&callback=googleFieldsInit&libraries=places&v=weekly');

        parent::__construct($name, $title, $value);
    }

    public function __clone()
    {
        $this->fieldLatitude = clone $this->fieldLatitude;
        $this->fieldLongitude = clone $this->fieldLongitude;
        $this->fieldZoom = clone $this->fieldZoom;
    }

    /**
     * Builds a new longitude field based on the allowed currencies configured
     *
     * @return FormField
     */
    protected function buildLongitudeField()
    {
        $name = $this->getName();

        // Validate allowed currencies
        $longitudeValue = $this->fieldLongitude ? $this->fieldLongitude->dataValue() : null;
        $allowedCurrencies = $this->getAllowedCurrencies();
        if (count($allowedCurrencies ?? []) === 1) {
            // Hidden field for single longitude
            $field = HiddenField::create("{$name}[Longitude]");
            reset($allowedCurrencies);
            $longitudeValue = key($allowedCurrencies ?? []);
        } elseif ($allowedCurrencies) {
            // Dropdown field for multiple currencies
            $field = DropdownField::create(
                "{$name}[Longitude]",
                _t('SilverStripe\\Forms\\MapField.FIELDLABELCURRENCY', 'Longitude'),
                $allowedCurrencies
            );
        } else {
            // Free-text entry for longitude value
            $field = TextField::create(
                "{$name}[Longitude]",
                _t('SilverStripe\\Forms\\MapField.FIELDLABELCURRENCY', 'Longitude')
            );
        }

        $field->setReadonly($this->isReadonly());
        $field->setDisabled($this->isDisabled());
        if ($longitudeValue) {
            $field->setValue($longitudeValue);
        }

        $field->setAttribute('data-goldfinch-map', 'longitude');

        $this->fieldLongitude = $field;
        return $field;
    }

    public function setSubmittedValue($value, $data = null)
    {
        if (empty($value)) {
            $this->value = null;
            $this->fieldLongitude->setValue(null);
            $this->fieldLatitude->setValue(null);
            $this->fieldZoom->setValue(null);
            return $this;
        }

        // Handle submitted array value
        if (!is_array($value)) {
            throw new InvalidArgumentException("Value is not submitted array");
        }

        // Update each field
        $this->fieldLongitude->setSubmittedValue($value['Longitude'], $value);
        $this->fieldLatitude->setSubmittedValue($value['Latitude'], $value);
        $this->fieldZoom->setSubmittedValue($value['Zoom'], $value);

        // Get data value
        $this->value = $this->dataValue();
        return $this;
    }

    public function setValue($value, $data = null)
    {
        if (empty($value)) {
            $this->value = null;
            $this->fieldLongitude->setValue(null);
            $this->fieldLatitude->setValue(null);
            $this->fieldZoom->setValue(null);
            return $this;
        }

        // dd($value);

        if ($value instanceof DBMap) {
            $value = [
                'Longitude' => $value->getLongitude(),
                'Latitude' => $value->getLatitude(),
                'Zoom' => $value->getZoom(),
            ];
        } elseif (!is_array($value)) {
            throw new InvalidArgumentException("Invalid longitude format");
        }

        // Save value
        $this->fieldLongitude->setValue($value['Longitude'], $value);
        $this->fieldLatitude->setValue($value['Latitude'], $value);
        $this->fieldZoom->setValue($value['Zoom'], $value);
        $this->value = $this->dataValue();
        return $this;
    }

    /**
     * Get value as DBMap object useful for formatting the number
     *
     * @return DBMap
     */
    protected function getDBMap()
    {
        return DBMap::create_field('Money', [
            'Longitude' => $this->fieldLongitude->dataValue(),
            'Latitude' => $this->fieldLatitude->dataValue(),
            'Zoom' => $this->fieldZoom->dataValue()
        ]);
    }

    public function dataValue()
    {
        // Non-localised money
        return $this->getDBMap()->getValue();
    }

    public function Value()
    {
        // Localised money
        return $this->getDBMap()->Nice();
    }

    /**
     * 30/06/2009 - Enhancement:
     * SaveInto checks if set-methods are available and use them
     * instead of setting the values in the money class directly. saveInto
     * initiates a new Money class object to pass through the values to the setter
     * method.
     *
     * (see @link MapFieldTest_CustomSetter_Object for more information)
     *
     * @param DataObjectInterface|Object $dataObject
     */
    public function saveInto(DataObjectInterface $dataObject)
    {
        $fieldName = $this->getName();
        if ($dataObject->hasMethod("set$fieldName")) {
            $dataObject->$fieldName = $this->getDBMap();
        } else {
            $longitudeField = "{$fieldName}Longitude";
            $latitudeField = "{$fieldName}Latitude";
            $zoomField = "{$fieldName}Zoom";

            $dataObject->$longitudeField = $this->fieldLongitude->dataValue();
            $dataObject->$latitudeField = $this->fieldLatitude->dataValue();
            $dataObject->$zoomField = $this->fieldZoom->dataValue();
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

        $this->fieldLatitude->setReadonly($bool);
        $this->fieldLongitude->setReadonly($bool);

        return $this;
    }

    public function setDisabled($bool)
    {
        parent::setDisabled($bool);

        $this->fieldLatitude->setDisabled($bool);
        $this->fieldLongitude->setDisabled($bool);

        return $this;
    }

    /**
     * Set list of currencies. Currencies should be in the 3-letter ISO 4217 longitude code.
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
            throw new InvalidArgumentException("Invalid longitude list");
        } elseif (!ArrayLib::is_associative($currencies)) {
            $currencies = array_combine($currencies ?? [], $currencies ?? []);
        }

        $this->allowedCurrencies = $currencies;

        // Rebuild longitude field
        $this->buildLongitudeField();
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
     * Validate this field
     *
     * @param Validator $validator
     * @return bool
     */
    public function validate($validator)
    {
        // Validate longitude
        $currencies = $this->getAllowedCurrencies();
        $longitude = $this->fieldLongitude->dataValue();
        if ($longitude && $currencies && !in_array($longitude, $currencies ?? [])) {
            $validator->validationError(
                $this->getName(),
                _t(
                    __CLASS__ . '.INVALID_CURRENCY',
                    'Longitude {longitude} is not in the list of allowed currencies',
                    ['longitude' => $longitude]
                )
            );
            return $this->extendValidationResult(false, $validator);
        }

        // Field-specific validation
        $result = $this->fieldLatitude->validate($validator) && $this->fieldLongitude->validate($validator);
        return $this->extendValidationResult($result, $validator);
    }

    public function setForm($form)
    {
        $this->fieldLongitude->setForm($form);
        $this->fieldLatitude->setForm($form);
        return parent::setForm($form);
    }
}
