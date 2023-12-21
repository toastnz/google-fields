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

        $this->fieldLatitude = TextField::create("{$name}[Latitude]", "Latitude");

        $this->fieldLatitude->setAttribute('data-goldfinch-map', 'latitude');

        $this->fieldZoom = TextField::create("{$name}[Zoom]", "Zoom");

        $this->fieldZoom->setAttribute('data-goldfinch-map', 'zoom');

        $this->buildLongitudeField();

        Requirements::css('goldfinch/google-fields:client/dist/google-fields-style.css');
        Requirements::javascript('goldfinch/google-fields:client/dist/google-fields.js');
        Requirements::javascript('//maps.googleapis.com/maps/api/js?key=' . Environment::getEnv('APP_GOOGLE_MAPS_KEY') . '&callback=googleFieldsInit&libraries=places&v=weekly');
        // Requirements::javascript('//maps.googleapis.com/maps/api/js?key=' . Environment::getEnv('APP_GOOGLE_MAPS_KEY') . '&callback=googleFieldsInit&v=weekly'); // without places library


        parent::__construct($name, $title, $value);
    }

    public function __clone()
    {
        $this->fieldLatitude = clone $this->fieldLatitude;
        $this->fieldLongitude = clone $this->fieldLongitude;
        $this->fieldZoom = clone $this->fieldZoom;
    }

    /**
     * Builds a new longitude field
     *
     * @return FormField
     */
    protected function buildLongitudeField()
    {
        $name = $this->getName();

        $longitudeValue = $this->fieldLongitude ? $this->fieldLongitude->dataValue() : null;

        $field = TextField::create("{$name}[Longitude]", "Longitude");

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
        return DBMap::create_field('Map', [
            'Longitude' => $this->fieldLongitude->dataValue(),
            'Latitude' => $this->fieldLatitude->dataValue(),
            'Zoom' => $this->fieldZoom->dataValue()
        ]);
    }

    public function dataValue()
    {
        // Non-localised
        return $this->getDBMap()->getValue();
    }

    public function Value()
    {
        // Localised
        return $this->getDBMap()->Nice();
    }

    /**
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
        $this->fieldZoom->setReadonly($bool);

        return $this;
    }

    public function mapReadonly()
    {
        $this->fieldLatitude->addExtraClass('readonly')->setAttribute('tabindex', '-1')->setAttribute('id', '__readonly__');
        $this->fieldLongitude->addExtraClass('readonly')->setAttribute('tabindex', '-1')->setAttribute('id', '__readonly__');
        $this->fieldZoom->addExtraClass('readonly')->setAttribute('tabindex', '-1')->setAttribute('id', '__readonly__');

        return $this;
    }

    public function mapHideExtra()
    {
        $this->addExtraClass('goldfinch-google-map-hide-extra-fields');

        return $this;
    }

    public function mapHideSearch()
    {
        $this->addExtraClass('goldfinch-google-map-hide-search');

        return $this;
    }

    public function setDisabled($bool)
    {
        parent::setDisabled($bool);

        $this->fieldLatitude->setDisabled($bool);
        $this->fieldLongitude->setDisabled($bool);
        $this->fieldZoom->setDisabled($bool);

        return $this;
    }

    /**
     * Validate this field
     *
     * @param Validator $validator
     * @return bool
     */
    public function validate($validator)
    {
        // return $this->extendValidationResult($result, $validator);
    }

    public function setForm($form)
    {
        $this->fieldLongitude->setForm($form);
        $this->fieldLatitude->setForm($form);
        return parent::setForm($form);
    }
}
