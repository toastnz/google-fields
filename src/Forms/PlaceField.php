<?php

namespace Goldfinch\GoogleFields\Forms;

use InvalidArgumentException;
use SilverStripe\Forms\FormField;
use SilverStripe\Forms\TextField;
use SilverStripe\Core\Environment;
use SilverStripe\Forms\HiddenField;
use SilverStripe\View\Requirements;
use SilverStripe\Forms\LiteralField;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\ORM\DataObjectInterface;
use Goldfinch\GoogleFields\ORM\FieldType\DBPlace;

class PlaceField extends FormField
{
    protected $schemaDataType = 'PlaceField';

    protected $settings = [
        'country' => 'nz',
    ];

    /**
     * @var HiddenField
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
     * @return HiddenField
     */
    public function getDataField()
    {
        return $this->fieldData;
    }

    public function getPreviewField()
    {
        return LiteralField::create(
            $this->getName() . 'Map',
            '<div class="ggp__preview" data-goldfinch-place="preview"></div>',
        );
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

    public function __construct($name, $title = null, $value = '')
    {
        $this->setName($name);
        $this->fieldData = HiddenField::create("{$name}[Data]", 'Data');

        $this->fieldData->setAttribute('data-goldfinch-place', 'data');

        $this->buildAddressField();

        Requirements::css(
            'goldfinch/google-fields:client/dist/google-fields-style.css',
        );
        Requirements::javascript(
            'goldfinch/google-fields:client/dist/google-fields.js',
        );

        if (Environment::hasEnv('APP_GOOGLE_MAPS_KEY')) {
            $key = Environment::getEnv('APP_GOOGLE_MAPS_KEY');
        } else {
            $cfg = SiteConfig::current_site_config();
            if ($cfg->GoogleCloud && $cfg->GoogleCloudAPIKey) {
                $key = $cfg->GoogleCloudAPIKey;
            } else {
                $key = '';
            }
        }

        Requirements::javascript(
            '//maps.googleapis.com/maps/api/js?key=' .
                $key .
                '&callback=googleFieldsInit&libraries=places&v=weekly',
        );

        parent::__construct($name, $title, $value);
    }

    public function __clone()
    {
        $this->fieldData = clone $this->fieldData;
        $this->fieldAddress = clone $this->fieldAddress;
    }

    /**
     * Builds a new address field
     *
     * @return FormField
     */
    protected function buildAddressField()
    {
        $name = $this->getName();

        $addressValue = $this->fieldAddress
            ? $this->fieldAddress->dataValue()
            : null;

        $field = TextField::create("{$name}[Address]", 'Address');

        $field->setReadonly($this->isReadonly());
        $field->setDisabled($this->isDisabled());
        if ($addressValue) {
            $field->setValue($addressValue);
        }

        $field->setAttribute('data-goldfinch-place', 'address');

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
            throw new InvalidArgumentException('Value is not submitted array');
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

        if ($value instanceof DBPlace) {
            $value = [
                'Address' => $value->getAddress(),
                'Data' => $value->getData(),
            ];
        } elseif (!is_array($value)) {
            throw new InvalidArgumentException('Invalid address format');
        }

        // Save value
        $this->fieldAddress->setValue($value['Address']);
        $this->fieldData->setValue($value['Data']);
        $this->value = $this->dataValue();

        $this->fieldAddress->setAttribute(
            'data-settings',
            json_encode($this->getSettings()),
        );

        return $this;
    }

    /**
     * Get value as DBPlace object useful for formatting the number
     *
     * @return DBPlace
     */
    protected function getDBPlace()
    {
        return DBPlace::create_field('Place', [
            'Address' => $this->fieldAddress->dataValue(),
            'Data' => $this->fieldData->dataValue(),
        ]);
    }

    public function dataValue()
    {
        // Non-localised
        return $this->getDBPlace()->getValue();
    }

    public function Value()
    {
        // Localised
        return $this->getDBPlace()->Nice();
    }

    /**
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

            if (
                $dataObject->$addressField &&
                $dataObject->$addressField != ''
            ) {
                $dataObject->$dataField = $this->fieldData->dataValue();
            } else {
                $dataObject->$dataField = null;
            }
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

    public function placeHidePreview()
    {
        $this->addExtraClass('goldfinch-google-place-hide-preview');

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
        $this->fieldAddress->setForm($form);
        $this->fieldData->setForm($form);
        return parent::setForm($form);
    }
}
