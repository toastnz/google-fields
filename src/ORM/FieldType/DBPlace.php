<?php

namespace Goldfinch\GoogleFields\ORM\FieldType;

use SilverStripe\i18n\i18n;
use SilverStripe\View\ArrayData;
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
     * Get Google link
     *
     * @return string
     */
    public function Link()
    {
        $latitude = $this->getLatitude();
        $longitude = $this->getLongitude();

        return 'https://www.google.com/maps/search/?api=1&query=' . $latitude . ',' . $longitude;
    }

    public function getParse($key = null)
    {
        $data = $this->getData();

        if (!$data) {
          return null;
        }

        $data = json_decode($data, true);

        $parse = [
            'Subpremise' => null,
            'StreetNumber' => null,
            'StreetName' => null,
            'Suburb' => null,
            'Subarea' => null,
            'Region' => null,
            'District' => null,
            'Country' => null,
            'Postcode' => null,

            'PlaceName' => $data['name'],
            'Longitude' => $data['geometry']['location']['lng'],
            'Latitude' => $data['geometry']['location']['lat'],
        ];

        foreach ($data['address_components'] as $component)
        {
            if (in_array('subpremise', $component['types']))
            {
                $parse['Subpremise'] = $component['long_name'];
            }
            else if (in_array('street_number', $component['types']))
            {
                $parse['StreetNumber'] = $component['long_name'];
            }
            else if (in_array('route', $component['types']))
            {
                $parse['StreetName'] = $component['long_name'];
            }
            else if (in_array('locality', $component['types']))
            {
                $parse['Suburb'] = $component['long_name'];
            }
            else if (in_array('sublocality', $component['types']))
            {
                $parse['Subarea'] = $component['long_name'];
            }
            else if (in_array('administrative_area_level_1', $component['types']))
            {
                $parse['Region'] = $component['long_name'];
            }
            else if (in_array('administrative_area_level_2', $component['types']))
            {
                $parse['District'] = $component['long_name'];
            }
            else if (in_array('country', $component['types']))
            {
                $parse['Country'] = $component['long_name'];
            }
            else if (in_array('postal_code', $component['types']))
            {
                $parse['Postcode'] = $component['long_name'];
            }
        }

        return $key ? (isset($parse[$key]) ? $parse[$key] : null) : $parse;
    }

    public function getPlaceName()
    {
        return $this->getParse('PlaceName');
    }

    public function getLatitude()
    {
        return $this->getParse('Latitude');
    }

    public function getLongitude()
    {
        return $this->getParse('Longitude');
    }

    public function getSubpremise()
    {
        return $this->getParse('Subpremise');
    }

    public function getStreetNumber()
    {
        return $this->getParse('StreetNumber');
    }

    public function getStreetName()
    {
        return $this->getParse('StreetName');
    }

    public function getSuburb()
    {
        return $this->getParse('Suburb');
    }

    public function getSubarea()
    {
        return $this->getParse('Subarea');
    }

    public function getRegion()
    {
        return $this->getParse('Region');
    }

    public function getDistrict()
    {
        return $this->getParse('District');
    }

    public function getCountry()
    {
        return $this->getParse('Country');
    }

    public function getPostcode()
    {
        return $this->getParse('Postcode');
    }

    /**
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
        return PlaceField::create($this->getName(), $title);
            // ->setLocale($this->getLocale());
    }
}
