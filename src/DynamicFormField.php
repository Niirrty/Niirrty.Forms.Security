<?php
/**
 * @author     Ni Irrty <niirrty+code@gmail.com>
 * @copyright  © 2017-2021, Ni Irrty
 * @package    Niirrty\Forms\Security
 * @since      2017-11-03
 * @version    0.4.0
 */


declare( strict_types=1 );


namespace Niirrty\Forms\Security;


/**
 * This class define all data of an dynamic form field with an random generated name.
 *
 * A hidden form field must be defined as an part of the form that should be secured, with an random generated
 * form name. The required  information about the dynamic form field name is transmitted by the session.
 *
 * Usage-Example
 *
 * <code>
 * $dynamicField = new DynamicFormField();
 * if ( $dynamicField->isValidRequest() )
 * {
 *    // The field is permitted successful => handle the real form data
 * }
 * else
 * {
 *    // No Request => show the form with the required form field
 *    // Example of output the required hidden form field:
 *    echo $dynamicField->buildHiddenFieldHtml();
 * }
 * </code>
 */
class DynamicFormField implements ISecurityCheck
{


    #region // – – –   P R O T E C T E D   F I E L D S   – – – – – – – – – – – – – – – – – – – – – –

    /**
     * The required name of the form field for incoming form data requests. It can also been undefined if no form
     * data was generated before
     *
     * @var string|null
     */
    protected ?string $_inName;

    /**
     * This field name should be used for the associated form field for a new request
     *
     * @var string
     */
    protected string $_outName;

    /**
     * Saves the state if we have currently a request. It says noting about the validity!
     *
     * @var boolean
     */
    protected bool $_isRequest;

    /**
     * Saves the state if we have currently an VALID request.
     *
     * @var boolean
     */
    protected bool $_isValidRequest;

    /**
     * Use this session var to store the name of the dynamic form field.
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
    protected string $_sessionFieldName;

    private const ALPHA_CHARS = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ_';

    private const WORD_CHARS  = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789_';

    private static array $lastRandoms = [];

    #endregion


    #region // – – –   P U B L I C   C O N S T R U C T O R   – – – – – – – – – – – – – – – – – – – –

    /**
     * Init a new instance.
     *
     * @param string $value            This value should be used as current field value.
     * @param string $sessionFieldName Use this session var to store the name of the dynamic form field. To be a
     *                                 little bit more flexible you can also define a field name that say you will
     *                                 store the info in an associative array. You can define it like this:
     *                                 'FormStamps[MyForm]' or 'FormStamps.MyForm' Both means the same
     *                                 $_SESSION[ 'FormStamps' ][ 'MyForm' ] But its only supported one array level.
     *                                 Deeper will not work!
     */
    public function __construct( protected string $value = '1', string $sessionFieldName = 'NDFF.LastFieldName' )
    {

        if ( empty( $sessionFieldName ) )
        {
            $sessionFieldName = 'NDFF.LastFieldName';
        }
        $this->_sessionFieldName = $sessionFieldName;

        $this->reload();

    }

    #endregion


    #region // – – –   P U B L I C   M E T H O D S   – – – – – – – – – – – – – – – – – – – – – – – –


    #region = = =   G E T T E R S   = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = =

    /**
     * Returns the required name of the form field for incoming form data requests. It can also been undefined if no
     * form data was generated before.
     *
     * @return string|null
     */
    public function getInName(): ?string
    {

        return $this->_inName;

    }

    /**
     * Returns the state if we have currently a valid request.
     *
     * @return bool
     */
    public function isValidRequest(): bool
    {

        return $this->_isValidRequest;

    }

    /**
     * Returns the state if we have currently a request.
     *
     * @return bool
     */
    public function isRequest(): bool
    {

        return $this->_isRequest;

    }

    /**
     * Returns this field name, that should be used for the associated form field for a new request.
     *
     * @return string
     */
    public function getOutName(): string
    {

        return $this->_outName;

    }

    /**
     * Returns the value that should be used as current field value.
     *
     * @return string
     */
    public function getValue(): string
    {

        return $this->value;

    }

    /**
     * Returns the name of the session var to store the name of the dynamic form field.
     *
     * To be a little bit more flexible you can also define a field name that say you will store the info in an
     * associative array.
     *
     * You can define it like this: 'DFF[MyForm]' or 'DFF.MyForm'
     *
     * Both means the same $_SESSION[ 'DFF' ][ 'MyForm' ]
     *
     * But its only supported one array level. Deeper will not work!
     *
     * @return string
     */
    public function getSessionFieldName(): string
    {

        return $this->_sessionFieldName;

    }

    #endregion


    #region = = =   S E T T E R S   = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = =

    /**
     * Sets the name of the session var to store the name of the dynamic form field.
     *
     * To be a little bit more flexible you can also define a field name that say you will store the info in an
     * associative array.
     *
     * You can define it like this: 'DFF[MyForm]' or 'DFF.MyForm'
     *
     * Both means the same $_SESSION[ 'DFF' ][ 'MyForm' ]
     *
     * But its only supported one array level. Deeper will not work!
     *
     * Do'nt forget to call reload after you have done all you're changes!
     *
     * @param string $sessionFieldName
     *
     * @return DynamicFormField
     */
    public function setSessionFieldName( string $sessionFieldName ): DynamicFormField
    {

        $this->_sessionFieldName = $sessionFieldName;

        return $this;

    }

    /**
     * Sets the value that should be used as current field value.
     *
     * Do'nt forget to call reload after you have done all you're changes!
     *
     * @param string $value
     *
     * @return DynamicFormField
     */
    public function setValue( string $value ): DynamicFormField
    {

        $this->value = $value;

        return $this;

    }

    #endregion


    /**
     * Build the hidden form field and returns it.
     *
     * @param boolean     $asXhtml Generate an XHTML conform HTML element?
     * @param string|null $id      An optional ID attribute
     *
     * @return string
     */
    public function buildHiddenFieldHtml( bool $asXhtml = false, ?string $id = null ): string
    {

        $html = '<input type="hidden" name="' . $this->_outName . '" value="' . \htmlentities( $this->value ) . '"';

        if ( ! empty( $id ) )
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
        $this->_outName = static::BuildRandomWord( 6 );

        if ( ! SessionHelper::FieldExists( $this->_sessionFieldName ) )
        {
            $this->_inName = '';
            return;
        }

        $this->_inName = SessionHelper::GetFieldValue( $this->_sessionFieldName, '' );

        SessionHelper::SetFieldValue( $this->_sessionFieldName, $this->_outName );

        if ( empty( $this->_inName ) )
        {
            return;
        }

        $this->_isRequest = true;

        if ( isset( $_POST ) && \count( $_POST ) > 0 )
        {
            // Its an POST request
            if ( \filter_has_var( INPUT_POST, $this->_inName ) &&
                 \filter_input( INPUT_POST, $this->_inName ) == $this->value )
            {
                $this->_isValidRequest = true;
            }
        }

    }

    #endregion


    /**
     * Creates a word at random determined characters from range [A-Za-z0-9_], always starting with a alphabethic
     * character or '_'. The the resulting word length is a random value between $minLength and $maxLength
     *
     * @param int $minLength The minimal length of the word to generate.
     * @param int $maxLength The maximal length of the word to generate.
     *
     * @return string The generated word.
     */
    public static function BuildRandomWord( int $minLength = 5, int $maxLength = 12 ): string
    {

        // Define the max allowed index for some word characters
        $maxIndex = strlen( static::WORD_CHARS ) - 1;
        // Define the max allowed index for some alphanumeric characters
        $maxIndex1 = strlen( static::ALPHA_CHARS ) - 1;

        // Initialize the resulting value variable
        $res = '';
        // and the current length of the resulting string
        $resLength = 0;
        $len = $minLength;

        // Set it to 2 (the min allowed length) if its lower than 2
        if ( $len < 2 )
        {
            $len = 2;
        }
        if ( $maxLength > $len )
        {
            $len = mt_rand( $len, $maxLength );
        }

        // Loop if it breaks, if a required condition is reached
        while ( true )
        {
            while ( $resLength < $len )
            {
                // Fill with random letters while $resLength is not reached
                $res .= static::WORD_CHARS[ mt_rand( 0, $maxIndex ) ];
                ++$resLength;
            }

            if ( !preg_match( '~^[A-Za-z_].+$~', $res ) )
            {
                // If the resulting string does not start with A-Za-z_ replace first char with a random letter.
                $res = static::ALPHA_CHARS[ mt_rand( 0, $maxIndex1 ) ] . substr( $res, 1 );
            }

            if ( in_array( $res, self::$lastRandoms ) )
            {
                // The defined random word was used last time while current script call. Dont use it
                $resLength = 0;
                $res = '';
                continue;
            }

            // Remember as already used.
            self::$lastRandoms[] = $res;

            // We are done here
            break;

        }

        // Return the resulting word.
        return $res;

    }


}

