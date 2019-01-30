<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 8/29/18
 * Time: 2:28 PM
 */

namespace Rri\Bundle\ShippingBundle\EventListener;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Rri\Bundle\ShippingBundle\Model\AddressValidationResult;
use SimpleXMLElement;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;


class ShippingAddressListener
{

    /** @var ContainerInterface */
    private $container;

    const UPS_ADDRESS_VALIDATION_URL = 'oro_ups.address.validation.url';
    const UPS_ADDRESS_VALIDATION_NUMBER = 'oro_ups.address.validation.number';
    const UPS_ADDRESS_VALIDATION_USER = 'oro_ups.address.validation.user';
    const UPS_ADDRESS_VALIDATION_PASSWORD = 'oro_ups.address.validation.passowrd';

    const UPS_ADDRESS_VALIDATION_LOG_ENABLE = 'oro_ups.address.validation.log.enable';

    const ADDRESS_LINE = 'AddressLine';
    const POLITICAL_DIVISION_1 = 'PoliticalDivision1';
    const POLITICAL_DIVISION_2 = 'PoliticalDivision2';
    const COUNTRY_CODE = 'CountryCode';
    const POST_CODE_PRIMARY_LOW = 'PostcodePrimaryLow';

    /**
     * @var string
     */
    private $validationUrl;
    /**
     * @var string
     */
    private $accessLicenseNumber;
    /**
     * @var string
     */
    private $userId;
    /**
     * @var string
     */
    private $password;
    /**
     * @var boolean
     */
    private $enbaleUPSRequestLog;


    /**
     * ShippingAddressListener constructor.
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }


    public function onKernelRequestBeforeRouting(GetResponseEvent $event)
    {
        $request = $event->getRequest();
        if (strstr($request->getRequestUri(),'/customer/checkout') == null) {
            return;
        }

        $this->initial();

        $addressAttribute = $this->buildRequestAddress();

        if ($addressAttribute == null || count($addressAttribute) == 0) {
            return;
        }

        $concatStr = $addressAttribute[$this::ADDRESS_LINE]
            .$addressAttribute[$this::POLITICAL_DIVISION_1]
            .$addressAttribute[$this::POLITICAL_DIVISION_2]
            .$addressAttribute[$this::COUNTRY_CODE]
            .$addressAttribute[$this::POST_CODE_PRIMARY_LOW];

        if ($request->getSession() == null) {
            $request->setSession(new Session());
        }
        $lastStr = $request->getSession()->get('concatStr');
        $request->getSession()->set('concatStr',$concatStr);

        if (strcmp($lastStr , $concatStr) == 0) {
            return;
        }

        if (array_key_exists($concatStr,$request->getSession()->all())) {
            return;
        }

        $addressValidationResponse = $this->validateAddress($addressAttribute);
        if ($addressValidationResponse == null || $addressValidationResponse->getValidateOrNot() == 1) {
            $request->getSession()->all()[$concatStr] = $addressValidationResponse;
        }
        else {
            $event->setResponse(new Response($this->buildResponseJson($addressValidationResponse)));
        }
    }

    /**
     * intial
     */
    function initial () {
        $this->validationUrl = $this->container->getParameter($this::UPS_ADDRESS_VALIDATION_URL);
        $this->accessLicenseNumber = $this->container->getParameter($this::UPS_ADDRESS_VALIDATION_NUMBER);
        $this->userId = $this->container->getParameter($this::UPS_ADDRESS_VALIDATION_USER);
        $this->password = $this->container->getParameter($this::UPS_ADDRESS_VALIDATION_PASSWORD);
        $this->enbaleUPSRequestLog = $this->container->getParameter($this::UPS_ADDRESS_VALIDATION_LOG_ENABLE);
    }

    /**
     * buildRequestAddress
     *
     * @return array
     */
    private function buildRequestAddress() {
        if (! $_POST) {
            return null;
        }

        if (! array_key_exists('oro_workflow_transition',$_POST)) {
            return null;
        }

        $shippingAddress = $_POST['oro_workflow_transition']['shipping_address'];
        if (! $shippingAddress) {

        }

        $addressAttribute = [
            $this::ADDRESS_LINE => $shippingAddress['street'],
            $this::POLITICAL_DIVISION_1 => $shippingAddress['region'],
            $this::POLITICAL_DIVISION_2 => $shippingAddress['city'],
            $this::COUNTRY_CODE => $shippingAddress['country'],
            $this::POST_CODE_PRIMARY_LOW => $shippingAddress['postalCode']
        ];

        return $addressAttribute;
    }


    /**
     * @param $requestAddress
     * @return AddressValidationResult
     */
    function validateAddress ($requestAddress) {
        try {
            // Create AccessRequest XMl
            $accessRequestXML = new SimpleXMLElement ( "<AccessRequest></AccessRequest>" );
            $accessRequestXML->addChild ( "AccessLicenseNumber", $this->accessLicenseNumber );
            $accessRequestXML->addChild ( "UserId", $this->userId );
            $accessRequestXML->addChild ( "Password", $this->password );

            // Create AddressValidationRequest XMl
            $xavRequestXML = new SimpleXMLElement ( "<AddressValidationRequest ></AddressValidationRequest >" );
            $request = $xavRequestXML->addChild ( 'Request' );
            //Indicates the action to be taken by the XML service. Must be 'XAV'.
            $request->addChild ( "RequestAction", "XAV" );
            /*
                Identifies the optional processing to be performed.
                If not present or invalid value then the default will
                be used.
                1= Address Validation (Default)
                2= Address Classification
                3= Address Validation and Address Classification.
                For a list of valid values, refer to the Address
                Validation API Supported Countries/Territories in
                the Appendix.
             */
            $request->addChild ( "RequestOption", "3" );

            $address = $xavRequestXML->addChild ( 'AddressKeyFormat' );
            /*
                Address line (street number, street name and treet type) used for street level information.
                Additional secondary information (apartment, suite,floor, etc.)
                Applicable to US and PR only.Ignored if user selects theRegionalRequestIndicator.
             */
            //$address->addChild ( "AddressLine", "279 Philli Lane" );
            //$address->addChild ( "PoliticalDivision2", "Tulsa" );       //City or town name
            //$address->addChild ( "PoliticalDivision1", "Oklahoma" );    //State or Province/Territory name.
            //$address->addChild ( "PostcodePrimaryLow", "74136" );       //Postal Code.
            //$address->addChild ( "CountryCode", "US" );                 //Country/Territory Code.
            foreach ($requestAddress as $attrKey=>$attrValue) {
                $address->addChild ($attrKey , $attrValue);
            }

            $requestXML = $accessRequestXML->asXML () . $xavRequestXML->asXML ();

            $form = array (
                'http' => array (
                    'method' => 'POST',
                    'header' => 'Content-type: application/x-www-form-urlencoded',
                    'content' => "$requestXML"
                )
            );

            // get request
            $request = stream_context_create ( $form );
            $browser = fopen ( $this->validationUrl, 'rb', false, $request );
            if (! $browser) {
                throw new Exception ( "[RriShippingBundle] [ShippingAddressListener] [validateAddress] connection failed." );
            }

            // get response
            $response = stream_get_contents ( $browser );

            if ($response == false) {
                throw new Exception ( "[RriShippingBundle] [ShippingAddressListener] [validateAddress] bad data!" );
            }
            else {
                if ($this->enbaleUPSRequestLog) {
                    // save request and response to file
                    $outputFileName = "XOLTResult.xml";
                    $fw = fopen ( $outputFileName, 'a' );
                    fwrite ( $fw, "Request: \n" . $requestXML );
                    fwrite ( $fw, "Response: \n" . $response . "\n\n\n" );
                    fclose ( $fw );
                }
            }

            // get response status
            $resp = new SimpleXMLElement ( $response );

            $addressValidationResult = new AddressValidationResult();
            $addressValidationResult->setResponseStatusCode($resp->Response->ResponseStatusCode);
            $addressValidationResult->setResponseStatusDescription($resp->Response->ResponseStatusDescription);
            $addressValidationResult->setAddressClassificationCode($resp->AddressClassification->Code);
            $addressValidationResult->setAddressClassificationDescription($resp->AddressClassification->Description);
            $success = 0;
            if ($resp->ValidAddressIndicator) {
                $success = 1;
            }
            if ($resp->AmbiguousAddressIndicator) {
                $success = 0;
            }
            if ($resp->NoCandidatesIndicator) {
                $success = 2;
            }
            $addressValidationResult->setValidateOrNot($success);
            if ($resp->AddressKeyFormat) {
                $addressValidationResult->setAddressLine($resp->AddressKeyFormat->AddressLine);
                $addressValidationResult->setRegion($resp->AddressKeyFormat->Region);
                $addressValidationResult->setPoliticalDivision2($resp->AddressKeyFormat->PoliticalDivision2);
                $addressValidationResult->setPostcodePrimaryLow($resp->AddressKeyFormat->PostcodePrimaryLow);
                $addressValidationResult->setCountryCode($resp->AddressKeyFormat->CountryCode);
            }
            return $addressValidationResult;
        } catch ( Exception $ex ) {
            die("[RriShippingBundle] [ShippingAddressListener] [validateAddress] error occurs!");
        }

    }

    /**
     * @param AddressValidationResult $result
     * @return string
     */
    private function buildResponseJson (AddressValidationResult $result) {
        $jsonStr = '{"ResponseStatusCode":'
            .$result->getResponseStatusCode()
            .',"ResponseStatusDescription":"'
            .$result->getResponseStatusDescription()
            .'","AddressClassificationCode":'
            .$result->getAddressClassificationCode()
            .',"AddressClassificationDescription":"'
            .$result->getAddressClassificationDescription()
            .'","ValidateOrNot":'
            .$result->getValidateOrNot();

        if ($result->getValidateOrNot() === 2) {
            return $jsonStr.'}';
        }
        else {
            return $jsonStr.',"AddressLine":"'
            .$result->getAddressLine()
            .'","Region":"'
            .$result->getRegion()
            .'","PoliticalDivision2":"'
            .$result->getPoliticalDivision2()
            .'","PostcodePrimaryLow":"'
            .$result->getPostcodePrimaryLow()
            .'","CountryCode":"'
            .$result->getCountryCode()
            .'"}';
        }
    }
}
