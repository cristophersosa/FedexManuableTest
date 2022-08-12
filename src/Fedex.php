<?php

namespace Cristophersosa\FedexManuableTest;

class Fedex
{

    protected $key;
    protected $password;
    protected $account;
    protected $meter;
    protected $language;
    protected $locale;

    public function __construct(String $key, String $password, int $account, int $meter, String $language = "es", String $locale = "mx")
    {
        //Construct object Fedex with user credentials
        $this->key = $key;
        $this->password = $password;
        $this->account = $account;
        $this->meter = $meter;
        $this->language = $language;
        $this->locale = $locale;
    }

    public function getRates(array $params)
    {
        //Get body request
        $bodyRequest = $this->constructBodyRequest($params);

        $URL = "https://wsbeta.fedex.com:443/xml";
        $connection = curl_init();

        curl_setopt($connection, CURLOPT_URL, $URL);
        curl_setopt($connection, CURLOPT_HTTPGET, false);
        curl_setopt($connection, CURLOPT_HTTPHEADER, array('Content-Type: application/xml'));
        curl_setopt($connection, CURLOPT_POST, 1);
        curl_setopt($connection, CURLOPT_POSTFIELDS, $bodyRequest);
        curl_setopt($connection, CURLOPT_HEADER, false);
        curl_setopt($connection, CURLOPT_RETURNTRANSFER, 1);

        $response = curl_exec($connection);

        //Replace 'v13:' cause when response gets error, every key starts with 'v13:'
        $response = str_replace('v13:', '', $response);

        $xml = simplexml_load_string($response, "SimpleXMLElement", LIBXML_NOCDATA);
        $json = json_encode($xml);
        $response = json_decode($json, TRUE);

        $data = [];

        if ($response['HighestSeverity'] == "SUCCESS") {
            foreach ($response['RateReplyDetails'] as $rate) {
                $item = $this->getRatePriceCurrency($rate);
                $item = [
                    "price" => $item['Amount'],
                    "currency" => $item['Currency'],
                    "service_level" => [
                        "name" => ucwords(str_replace('_', ' ', $rate['ServiceType'])),
                        "token" => $rate['ServiceType']
                    ]
                ];
                array_push($data, $item);
            }
            return $data;
        } else {
            return $response;
        }
    }

    private function constructBodyRequest(array $params)
    {
        $data = [
            "WebAuthenticationDetail" => [
                "UserCredential" => [
                    "Key" => $this->key,
                    "Password" => $this->password
                ]
            ],
            "ClientDetail" => [
                "AccountNumber" => $this->account,
                "MeterNumber" => $this->meter,
                "Localization" => [
                    "LanguageCode" => $this->language,
                    "LocaleCode" => $this->locale
                ]
            ],
            "Version" => [
                "ServiceId" => "crs",
                "Major" => 13,
                "Intermediate" => 0,
                "Minor" => 0
            ],
            "ReturnTransitAndCommit" => "true",
            "RequestedShipment" => [
                "DropoffType" => "REGULAR_PICKUP",
                "PackagingType" => "YOUR_PACKAGING",
                "Shipper" => [
                    "Address" => [
                        "StreetLines" => "",
                        "City" => "",
                        "StateOrProvinceCode" => "XX",
                        "PostalCode" => $params['address_from']['zip'],
                        "CountryCode" => strtoupper($params['address_from']['country']),
                    ],
                ],
                "Recipient" => [
                    "Address" => [
                        "StreetLines" => "",
                        "City" => "",
                        "StateOrProvinceCode" => "XX",
                        "PostalCode" => $params['address_to']['zip'],
                        "CountryCode" => strtoupper($params['address_to']['country']),
                        "Residential" => "false",
                    ]
                ],
                "ShippingChargesPayment" => [
                    "PaymentType" => "SENDER",
                ],
                "RateRequestTypes" => "ACCOUNT",
                "PackageCount" => "1",
                "RequestedPackageLineItems" => [
                    "GroupPackageCount" => "1",
                    "Weight" => [
                        "Units" => strtoupper($params['parcel']['mass_unit']),
                        "Value" => $params['parcel']['weight'],
                    ],
                    "Dimensions" => [
                        "Length" => $params['parcel']['length'],
                        "Width" => $params['parcel']['width'],
                        "Height" => $params['parcel']['height'],
                        "Units" => strtoupper($params['parcel']['distance_unit']),
                    ]
                ]
            ]
        ];

        $xmlData = new \SimpleXMLElement("<RateRequest xmlns='http://fedex.com/ws/rate/v13'></RateRequest>");

        $this->convertToXml($data, $xmlData);

        return $xmlData->asXML();
    }

    private function convertToXml($data, &$xmlData)
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $child = $xmlData->addChild($key);
                $this->convertToXml($value, $child);
            } else {
                $xmlData->addChild("$key", htmlspecialchars("$value"));
            }
        }
    }

    private function getRatePriceCurrency($rate){
        $actualRateType = $rate['ActualRateType'];

        //Loop to get the price and currency of rate type
        foreach($rate['RatedShipmentDetails'] as $rate){
            if($rate['ShipmentRateDetail']['RateType'] == $actualRateType){

                //Return object with the right amount and currency
                return $rate['ShipmentRateDetail']['TotalNetChargeWithDutiesAndTaxes'];

            }
        }
    }
}
