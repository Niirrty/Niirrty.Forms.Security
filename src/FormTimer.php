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


use function base64_decode;
use function base64_encode;
use function filter_has_var;
use function filter_input;
use function floatval;
use function max;
use function microtime;
use function str_replace;
use function strval;
use function substr;
use const INPUT_POST;


/**
 * This class allow you to define an time span of an valid web form request. It means you can define how long an
 * really user should need minimally, to fill out the form. The maximum request time is not restricted by this class
 * because its not important for doing the required job
 *
 * Please do not think its an summary for filling all required form fields. That's a fallacy! An form can also been
 * re-shown, for change some missed or wrong form field value or check an required checkbox. at least with all
 * required interaction 1.5 - 2 seconds. Not more! But it does the required job because bots send really fast. They
 * visit (scan) if they are "large" a lot of million pages in 24h. Time is money :-( so one second is an more
 * realistic time span for bots. So we are served well, with 1.5 seconds min request time.
 *
 * Here an short usage example for preferred method with storing the request microtime inside the session:
 *
 * <pre lang="php" class="php">
 *
 * use /Niirrty/Forms/Security/FormTimer;
 *
 * // Init the form timer
 * $formTimer = new FormTimer(
 *    true,                     // Use session?
 *    'FormTimer[NameOfMyForm]' // Session var name
 * );
 *
 * // $isPostRequest is an imaginary value that must be replaced bei you're code to check if an POST request of
 * // you're form exists
 * if ( $isPostRequest )
 * {
 *    if ( ! $formTimer->isValid() )
 *    {
 *       // no valid Timer request => Ignore this request and show the form
 *    }
 *    else
 *    {
 *       // Handle the valid request
 *    }
 * }
 * </pre>
 *
 * @package Niirrty\Forms\Security
 */
class FormTimer implements ISecurityCheck
{


    // <editor-fold desc="// – – –   P R O T E C T E D   F I E L D S   – – – – – – – – – – – – – – – – – – – – – –">


    /**
     * If the session should be used to transport the timestamp of the last user form view.
     *
     * If so you must also define the session field name by setSessionFieldName(). If not you have to define the
     * form field name by setFormFieldName() and the microtime will be stored by an hidden form field.
     *
     * @var string
     */
    protected $_useSession;

    /**
     * Use this session var to store the last form request microtime. It is only used if getUseSession() returns TRUE.
     *
     * To be a little bit more flexible you can also define a field name that say you will store the info in an
     * associative array.
     *
     * You can define it like this: 'FormStamps[MyForm]' or 'FormStamps.MyForm'
     *
     * Both means the same $_SESSION[ 'FormStamps' ][ 'MyForm' ]
     *
     * But its only supported one array level. Deeper will not work!
     *
     * @var string
     */
    protected $_sessionFieldName;

    /**
     * If the session should NOT be used to transport the timestamp of the last user form view and an form field
     * should be used, here you can define the name of the hidden form field, used for it.
     *
     * @var string
     */
    protected $_formFieldName;

    /**
     * The minimum required request time for an valid form request.
     *
     * @var float
     */
    protected $_minRequestTime;

    protected $_isRequest;

    protected $_isValidRequest;

    /**
     * The microtime timestamp of the current form view.
     *
     * @var float
     */
    protected $_currentFormStamp;

    /**
     * The microtime timestamp of the last form submit.
     *
     * @var float|null
     */
    protected $_lastFormStamp;

    // </editor-fold>


    // <editor-fold desc="// – – –   C O N S T A N T S   – – – – – – – – – – – – – – – – – – – – – – – – – – – – –">

    /**
     * The default min request time
     *
     * @type float
     */
    const DEFAULT_MIN_REQUEST_TIME = 1.5;

    // </editor-fold>


    // <editor-fold desc="// – – –   P U B L I C   C O N S T R U C T O R   – – – – – – – – – – – – – – – – – – – –">

    /**
     * Init an new instance.
     *
     * @param boolean $useSession       Should the SESSION been used to store the last form request microtime?
     *                                  If so you must also define the $sessionFieldName Parameter. If not you have
     *                                  to define the $formFieldName and the microtime will be stored by an hidden
     *                                  form field.
     * @param string  $sessionFieldName Use this session var to store the last form request microtime. It is only used
     *                                  if $useSession is set to TRUE. To be a little bit more flexible you can also
     *                                  define a field name that say you will store the info in an associative array.
     *                                  You can define it like this 'FormStamps[MyForm]' or 'FormStamps.MyForm'. Both
     *                                  means the same $_SESSION[ 'FormStamps' ][ 'MyForm' ]. But its only supported
     *                                  one array level. Deeper will not work!
     * @param string  $formFieldName    If $useSession is FALSE, you have to define here the name of an hidden form
     *                                  field that should be used to store the last form request microtime.
     * @param float   $minRequestTime
     */
    public function __construct(
        bool $useSession, string $sessionFieldName, ?string $formFieldName = null,
        float $minRequestTime = self::DEFAULT_MIN_REQUEST_TIME )
    {

        $this->_useSession = $useSession;
        $this->_formFieldName = $formFieldName;
        $this->_minRequestTime = max( $minRequestTime, static::DEFAULT_MIN_REQUEST_TIME );
        $this->_sessionFieldName = $sessionFieldName;
        $this->_currentFormStamp = microtime( true );

        $this->reload();

    }

    // </editor-fold>


    // <editor-fold desc="// – – –   P U B L I C   M E T H O D S   – – – – – – – – – – – – – – – – – – – – – – – –">


    # <editor-fold desc="= = =   G E T T E R S   = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = =">

    /**
     * Returns if the session should NOT be used to transport the timestamp of the last user form view and an form
     * field should be used, the name of the hidden form field, used for it.
     *
     * @return string|null
     */
    public function getFormFieldName(): ?string
    {

        return $this->_formFieldName;

    }

    /**
     * Returns if currently a request info exists that can be used to check the last form request time.
     *
     * @return boolean
     */
    public function isRequest(): bool
    {

        return $this->_isRequest;

    }

    /**
     * Returns if the check of min. form request time was successful while the required info was found.
     *
     * @return boolean
     */
    public function isValidRequest(): bool
    {

        return $this->_isValidRequest;

    }

    /**
     * Returns the minimum required request time for an valid form request. Default 1.5 seconds is the best choice!
     *
     * @return float
     */
    public function getMinRequestTime(): float
    {

        return $this->_minRequestTime;

    }

    /**
     * Returns if the session should be used to transport the timestamp of the last user form view, the name of the
     * session field.
     *
     * @return string
     */
    public function getSessionFieldName(): string
    {

        return $this->_sessionFieldName;

    }

    /**
     * Returns if the session should been used to transport the required information? Otherwise an hidden form field
     * must be used.
     *
     * @return boolean
     */
    public function getUseSession(): bool
    {

        return $this->_useSession;

    }

    # </editor-fold>


    # <editor-fold desc="= = =   S E T T E R S   = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = =">

    /**
     * Sets if the session should NOT be used to transport the timestamp of the last user form view and an form
     * field should be used, the name of the hidden form field, used for it.
     *
     * Do'nt forget to call reload after you have done all you're changes!
     *
     * @param string|null $formFieldName
     *
     * @return FormTimer
     */
    public function setFormFieldName( ?string $formFieldName ): FormTimer
    {

        $this->_formFieldName = $formFieldName;

        return $this;

    }

    /**
     * Sets the minimum required request time for an valid form request. Minimal allowed value is 1.5 seconds.
     *
     * Do'nt forget to call reload after you have done all you're changes!
     *
     * @param float $minRequestTime Defaults to self::DEFAULT_MIN_REQUEST_TIME
     *
     * @return FormTimer
     */
    public function setMinRequestTime( float $minRequestTime = self::DEFAULT_MIN_REQUEST_TIME ): FormTimer
    {

        $this->_minRequestTime = max( $minRequestTime, self::DEFAULT_MIN_REQUEST_TIME );

        return $this;

    }

    /**
     * Sets if the session should be used to store the form view microtime, here the name of this session var.
     * Use this session var to store the last form request microtime. It is only used if getUseSession() returns TRUE.
     *
     * To be a little bit more flexible you can also define a field name that say you will store the info in an
     * associative array.
     *
     * You can define it like this: 'FormStamps[MyForm]' or 'FormStamps.MyForm'
     *
     * Both means the same $_SESSION[ 'FormStamps' ][ 'MyForm' ]
     *
     * But its only supported one array level. Deeper will not work!
     *
     * Do'nt forget to call reload after you have done all you're changes!
     *
     * @param string $sessionFieldName
     *
     * @return FormTimer
     */
    public function setSessionFieldName( string $sessionFieldName ): FormTimer
    {

        $this->_sessionFieldName = $sessionFieldName;

        return $this;

    }

    /**
     * Sets if the session should be used to transport the timestamp of the last user form view.
     *
     * If so you must also define the session field name by setSessionFieldName(). If not you have to define the
     * form field name by setFormFieldName() and the microtime will be stored by an hidden form field.
     *
     * Do'nt forget to call reload after you have done all you're changes!
     *
     * @param boolean $useSession
     *
     * @return FormTimer
     */
    public function setUseSession( bool $useSession ): FormTimer
    {

        $this->_useSession = $useSession;

        return $this;

    }

    # </editor-fold>


    /**
     * Build the hidden form field and returns it.
     *
     * @param boolean $asXhtml Generate an XHTML conform HTML element?
     * @param string  $id      An optional ID attribute
     *
     * @return string It only returns the form field if usSession is set to false and if an form field name is defined
     */
    public function buildHiddenFieldHtml( bool $asXhtml = false, ?string $id = null )
    {

        if ( $this->_useSession || !empty( $this->_formFieldName ) )
        {
            return '';
        }
        $html = '<input type="hidden" name="'
                . $this->_formFieldName
                . '" value="Uk7'
                . base64_encode( strval( $this->_currentFormStamp ) )
                . '"';
        if ( !empty( $id ) )
        {
            $html .= ' id="' . $id . '"';
        }
        if ( $asXhtml )
        {
            $html .= ' />';
        }
        else
        {
            $html .= '>';
        }

        return $html;
    }

    /**
     * Reloads the states isRequest and isValidRequest, after you have made some changes by the set methods.
     */
    public function reload()
    {

        $this->_isRequest = false;
        $this->_isValidRequest = false;

        if ( $this->_useSession && !empty( $this->_sessionFieldName ) )
        {
            // Using the session to store the require information
            if ( SessionHelper::FieldExists( $this->_sessionFieldName ) )
            {
                // The required session field exists
                $this->_isRequest = true;
                // Getting the microtime timestamp of the last form view
                $this->_lastFormStamp = floatval(
                    str_replace( ',', '.', SessionHelper::GetFieldValue( $this->_sessionFieldName ) )
                );
                // Remember if the form request was send after reaching the min request time span.
                $this->_isValidRequest = ( $this->_lastFormStamp + $this->_minRequestTime <= $this->_currentFormStamp );
            }
            // Register an new session value with current microtime timestamp
            SessionHelper::SetFieldValue( $this->_sessionFieldName, $this->_currentFormStamp );
        }
        else if ( !empty( $this->_formFieldName ) &&
                  filter_has_var( INPUT_POST, $this->_formFieldName ) )
        {
            // Using an hidden form field to permit the required information
            $this->_isRequest = true;
            // Getting the microtime timestamp of the last form view
            $this->_lastFormStamp = floatval(                                // Convert to float
                str_replace(                                                  // Replace , with .
                    ',',
                    '.',
                    base64_decode(                                             // Decode the form field value
                        substr(                                                 // Remove the first 3 dummy chars
                            filter_input( INPUT_POST, $this->_formFieldName ),  // Get the form field value
                            3
                        )
                    )
                )
            );
            // // Remember if the form request was send after reaching the min request time span.
            $this->_isValidRequest = ( $this->_lastFormStamp + $this->_minRequestTime <= $this->_currentFormStamp );
        }

        // We are done here...

    }


    // </editor-fold>


}

