<?php
/**
 * @author     Ni Irrty <niirrty+code@gmail.com>
 * @copyright  © 2017-2020, Ni Irrty
 * @package    Niirrty\Forms\Security
 * @since      2017-11-03
 * @version    0.3.0
 */


declare( strict_types=1 );


namespace Niirrty\Forms\Security;


use function filter_has_var;
use function filter_input;
use const INPUT_POST;


/**
 * This class allows you to easy secure you're web form by an "honeypot".
 *
 * A honeypot should do the same job like in real life. He is expected to lure something.
 *
 * In this case the honeypot should attract the bots. They see this field with an popular name like 'text' and will
 * fill it with the content of which he thinks that he would be the right.
 *
 * The idea behind this field is: An bot can normally not distinguish between visible and invisible form fields if
 * hidden by some CSS code. If so, the bot have no idea about the current visibility state and will fill it.
 *
 * The filling with something will be the identifier, that no human has send the last request, because the required
 * form field value is an empty string.
 *
 * <b>Why an text area form element is used?</b>
 *
 * Modern web browser supports the "auto fill" feature. If the browser thinks he known what content is to prefer for
 * an text input form field, maybe he does it also. That will generate a false-positive state "There must be an bot"
 * Text area fields normal will not be auto filled by browsers.
 *
 * Short example
 *
 * <code>
 * $honeypot = new \Niirrty\Forms\Security\Honeypot( 'mail' );
 * if ( $honeypot->isValidRequest() )
 * {
 *    // The honeypot field is permitted successful => handle the real form data
 * }
 * else
 * {
 *    // No Request => show the form with the required form field
 *    // First you need to write you're CSS code. Its only an example. Please use template engines!
 *    echo '&lt;style type="text/css"&gt;'
 *       , $honeypot->buildCSS( 'inv1s1ble' )
 *       , "&lt;/style&gt;\n";
 *    // Now output the label with an message for clients that do not support CSS. e.g. "do not fill this field"
 *    echo '&lt;label class="inv1s1ble"&glt;&amp;#80;l&amp;#101;a&amp;#115;e&amp;#32;d&amp;#111; &amp;#110;o&amp;#116;
 *    &amp;#102;i&amp;#108;l&amp;#32;t&amp;#104;i&amp;#115; &amp;#102;i&amp;#101;l&amp;#100;&lt;/label&gt;';
 *    // Example of output the required text area form field:
 *    echo $honeypot->buildFormField( true, 'inv1s1ble' );
 *    // ...
 * }
 * </code>
 */
class HoneyPot implements ISecurityCheck
{


    // <editor-fold desc="// – – –   P R O T E C T E D   F I E L D S   – – – – – – – – – – – – – – – – – – – – – –">


    /**
     * The name of the honeypot form field.
     *
     * @var string
     */
    protected $_fieldName;

    /**
     * What request type should be used? (use \INPUT_POST or \INPUT_GET constant)
     *
     * @var integer
     */
    protected $_requestType;

    /**
     * Is there an request?
     *
     * @type boolean
     */
    protected $_isRequest;

    /**
     * Is the current request an valid request?
     *
     * @type boolean
     */
    protected $_isValidRequest;

    // </editor-fold>


    // <editor-fold desc="// – – –   P U B L I C   C O N S T R U C T O R   – – – – – – – – – – – – – – – – – – – –">

    /**
     * Init a new instance.
     *
     * @param string $fieldName   The name of the honeypot form field.
     * @param int    $requestType What request type should be used? (use \INPUT_POST or \INPUT_GET constant)
     */
    public function __construct( string $fieldName, int $requestType = INPUT_POST )
    {

        $this->_fieldName = $fieldName;
        $this->_requestType = $requestType;

        $this->reload();

    }

    // </editor-fold>


    // <editor-fold desc="// – – –   P U B L I C   M E T H O D S   – – – – – – – – – – – – – – – – – – – – – – – –">


    # <editor-fold desc="= = =   G E T T E R S   = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = =">

    /**
     * Returns the name of the honeypot form field.
     *
     * @return string
     */
    public function getFieldName(): string
    {

        return $this->_fieldName;

    }

    /**
     * Returns what request type is used? (see \INPUT_POST or \INPUT_GET constant)
     *
     * @return int
     */
    public function getRequestType(): int
    {

        return $this->_requestType;

    }

    /**
     * Returns if an request with the defined honeypot field exists. Does not say anything if the required empty
     * value is defined!
     *
     * @return boolean
     */
    public function isRequest(): bool
    {

        return $this->_isRequest;

    }

    /**
     * Returns, if an request with the defined honeypot field exists and if it defines the required empty value.
     *
     * @return boolean
     */
    public function isValidRequest(): bool
    {

        return $this->_isValidRequest;

    }

    # </editor-fold>


    # <editor-fold desc="= = =   S E T T E R S   = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = =">

    /**
     * Sets the name of the honeypot form field.
     *
     * @param string $fieldName
     *
     * @return HoneyPot
     */
    public function setFieldName( string $fieldName ): HoneyPot
    {

        $this->_fieldName = $fieldName;

        return $this;

    }

    /**
     * Sets what request type should be used? (use \INPUT_POST or \INPUT_GET constant)
     *
     * @param int $requestType
     *
     * @return HoneyPot
     */
    public function setRequestType( int $requestType ): HoneyPot
    {

        $this->_requestType = $requestType;

        return $this;

    }

    // </editor-fold>


    /**
     * Builds the HTML text area or input form field honeypot that can be used inside you're form that should be
     * protected.
     *
     * @param bool   $asTextArea    Build as text area otherwise a input of type text is generated
     * @param string $hideClassName This CSS class must be defined to hide the honeypot form field.
     *
     * @return string
     */
    public function buildFormField( bool $asTextArea = true, string $hideClassName = 'inv1s1ble' ): string
    {

        if ( $asTextArea )
        {
            return '<textarea name="' . $this->_fieldName . '" class="' . $hideClassName . '" rows="5"></textarea>';
        }

        return '<input type="text" name="' . $this->_fieldName . '" class="' . $hideClassName . '" value="">';

    }

    /**
     * Builds the CSS rule for hiding the honeypot and optional label.
     *
     * @param string $hideClassName This CSS class must be defined to hide the honeypot form field.
     *
     * @return string
     */
    public function buildCSS( string $hideClassName = 'inv1s1ble' ): string
    {

        return '.' . $hideClassName . ' { display: none; visibility: hidden; }';
    }

    public function __toString()
    {

        return $this->buildFormField();

    }

    /**
     * Reloads the states isRequest and isValidRequest, after you have made some changes by the set methods.
     */
    public function reload()
    {

        $this->_isRequest = false;
        $this->_isValidRequest = false;

        if ( !filter_has_var( $this->_requestType, $this->_fieldName ) )
        {
            return;
        }

        $this->_isRequest = true;

        $honeypotValue = filter_input( $this->_requestType, $this->_fieldName );

        if ( '' === $honeypotValue )
        {
            $this->_isValidRequest = true;
        }

        // We are done here...

    }


    // </editor-fold>


}

