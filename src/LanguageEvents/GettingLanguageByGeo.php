<?php
namespace DevGroup\Multilingual\LanguageEvents;

class GettingLanguageByGeo implements GettingLanguage
{
    public static function gettingLanguage(languageEvent $event)
    {
        // ok we have at least geo object, try to find language for it
        if ($event->currentLanguageId === false && $event->multilingual->geo_default_language_forced === false) {
            $event->currentLanguageId = $event->multilingual->language_id_geo;
            $event->resultClass = self::class;
        }
    }


    public function getLanguageFromGeo()
    {
            // ok we have at least geo object, try to find language for it
            if ($this->geo instanceof GeoInfo) {
                $country = $this->geo->country;
                $searchOrder = [
                    'iso_3166_1_alpha_2',
                    'iso_3166_1_alpha_3',
                    'name',
                ];
                foreach ($searchOrder as $attribute) {
                    if (isset($country->$attribute)) {
                        $model = CountryLanguage::find()
                            ->where([$attribute => $country->$attribute])
                            ->one();
                        if ($model !== null) {
                            $this->language_id_geo = $model->language_id;
                            return;
                        }
                    }
                }
            }


        return $this->language_id_geo;
    }

}