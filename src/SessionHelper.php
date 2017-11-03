<?php
/**
 * @author     Ni Irrty <niirrty+code@gmail.com>
 * @copyright  ©2017, Ni Irrty
 * @package    Niirrty\Forms\Security
 * @since      2017-11-03
 * @version    0.1.0
 */

declare( strict_types=1 );


namespace Niirrty\Forms\Security;


/**
 * A small static session helper class.
 *
 * @package Niirrty\Forms\Security
 */
class SessionHelper
{


   // <editor-fold desc="// – – –   P R I V A T E   C O N S T R U C T O R   – – – – – – – – – – – – – – – – – – –">

   /**
    * Private constructor because the class should only by used by the static way.
    */
   private function __construct() {}

   // </editor-fold>


   // <editor-fold desc="// – – –   P U B L I C   S T A T I C   M E T H O D S   – – – – – – – – – – – – – – – – –">

   /**
    * Check if the defined session field exists.
    *
    * To be a little bit more flexible you can also define a field name that say you will store the info in an
    * associative array.
    *
    * You can define it like this: 'FormStamps[MyForm]' or 'FormStamps.MyForm'
    *
    * Both means the same $_SESSION[ 'FormStamps' ][ 'MyForm' ]
    *
    * But it only supports one array sub level. Deeper will not work!
    *
    * @param  string $fieldName
    * @return bool
    */
   public static function FieldExists( string $fieldName ) : bool
   {

      $fieldNameElements = static::extractFieldNameElements( $fieldName );

      if ( ! isset( $fieldNameElements[ 1 ] ) )
      {
         return isset( $_SESSION[ $fieldName ] );
      }

      return
         isset( $_SESSION[ $fieldNameElements[ 0 ] ] ) &&
         isset( $_SESSION[ $fieldNameElements[ 0 ] ][ $fieldNameElements[ 1 ] ] );

   }

   /**
    * Returns the value of an session var with defined name.
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
    * @param  string $fieldName
    * @param  mixed  $defaultValue
    * @return mixed
    */
   public static function GetFieldValue( string $fieldName, $defaultValue = false )
   {

      $fieldNameElements = static::extractFieldNameElements( $fieldName );

      if ( ! isset( $fieldNameElements[ 1 ] ) )
      {
         return isset( $_SESSION[ $fieldName ] ) ? $_SESSION[ $fieldName ] : $defaultValue;
      }

      $exists = isset( $_SESSION[ $fieldNameElements[ 0 ] ] )
                && isset( $_SESSION[ $fieldNameElements[ 0 ] ][ $fieldNameElements[ 1 ] ] );

      return $exists ? $_SESSION[ $fieldNameElements[ 0 ] ][ $fieldNameElements[ 1 ] ] : $defaultValue;

   }

   /**
    * Sets a value for session var with defined name.
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
    * @param  string $fieldName
    * @param  mixed  $value
    * @return boolean
    */
   public static function SetFieldValue( string $fieldName, $value )
   {

      $fieldNameElements = static::extractFieldNameElements( $fieldName );

      if ( ! isset( $fieldNameElements[ 1 ] ) )
      {
         if ( null == $value ) { unset( $_SESSION[ $fieldName ] ); }
         else { $_SESSION[ $fieldName ] = $value; }
         return true;
      }

      if ( ! isset( $_SESSION[ $fieldNameElements[ 0 ] ] ) )
      {
         $_SESSION[ $fieldNameElements[ 0 ] ] = [];
      }

      if ( ! \is_array( $_SESSION[ $fieldNameElements[ 0 ] ] ) )
      {
         return false;
      }


      if ( null == $value ) { unset( $_SESSION[ $fieldNameElements[ 0 ] ][ $fieldNameElements[ 1 ] ] ); }
      else { $_SESSION[ $fieldNameElements[ 0 ] ][ $fieldNameElements[ 1 ] ] = $value; }

      return true;

   }

   // </editor-fold>


   // <editor-fold desc="// – – –   P R O T E C T E D   S T A T I C   M E T H O D S   – – – – – – – – – – – – – –">

   protected static function extractFieldNameElements( $fieldName )
   {

      if ( ( false === \strpos( $fieldName, '[' ) ) &&
           ( false === \strpos( $fieldName, '.' ) ) )
      {
         // Use field name as it because there is no separator defined.
         return [ $fieldName ];
      }

      // Split at [ or .
      $elements = \preg_split( '~[\[.]~', $fieldName );

      if ( 2 !== \count( $elements ) )
      {
         // It not result as 2 elements => Use field name as it
         return [ $fieldName ];
      }

      // Remove some white spaces and square brackets from 2nd element
      $elements[ 1 ] = \trim( $elements[ 1 ], "\r\n\t []" );

      return $elements;

   }

   // </editor-fold>


}

