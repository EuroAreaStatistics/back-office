<?php

// Hilfsfunktionen um JSON zu verarbeiten

class Json {
  // raise an exception if there was an error while encoding or decoding
  private static function checkError() {
    $error = json_last_error();
    if ($error) throw new Exception("json_decode error ".$error);
  }

  // decode JSON string
  public static function decode($s) {
    $r = json_decode($s, TRUE);
    self::checkError();
    return $r;
  }

  // encode string as JSON string
  public static function encode($s) {
    $r = json_encode($s, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)."\n";
    self::checkError();
    return $r;
  }

  // encode string as JSON string for use in a <script> tag
  public static function encodeJS($s) {
    $r = json_encode($s);
    self::checkError();
    return $r;
  }
}
