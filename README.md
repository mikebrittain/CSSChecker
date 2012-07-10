# CSS Checker

A PHP script for searching your codebase for existing CSS selectors (classes and IDs) to identify possible dead-weight in your style sheets.

## Usage

php css_checker.php <path_to_css> <path_to_codebase>

  path_to_css       - CSS top-level path or path to a single CSS file (recommended: /path/to/html/css)
  path_to_codebase  - Search path for locating CSS usage (recommended: /path/to/html)

### Example
  
    # validate all selectors found in a single file
    $ php css_checker.php ~/www/htdocs/css/base.css ~/www/

    # validate selectors found in *any* CSS files in this path
    $ php css_checker.php ~/www/htdocs/css ~/www/

## Sample Output

    -----------------------------------------------------------------------------
    The following CSS files are possibly unused
    -----------------------------------------------------------------------------



    -----------------------------------------------------------------------------
    Selectors ignored as pseudo-classes
    -----------------------------------------------------------------------------

      input::-webkit-input-placeholder
      .input-group input::-webkit-input-placeholder
      input:-moz-placeholder
      .input-group input:-moz-placeholder


    -----------------------------------------------------------------------------
    Selectors ignored as tags
    -----------------------------------------------------------------------------

      *
      body
      table
      form
      td
      tr
      th
      a:link
      a:visited
      a:hover
      a:active
      img
      input
      textarea
      sup
      sub


    -----------------------------------------------------------------------------
    Selectors ignored because they don't have any classes or IDs
    -----------------------------------------------------------------------------



    -----------------------------------------------------------------------------
    Selectors likely to be unused (not found in HTML tags or as barewords)
     * Name that was not matched is listed in parens.
    -----------------------------------------------------------------------------

      #meta-header body   (#meta-header)
      #footer ul.bottom-margin   (.bottom-margin)
      p.footer-currency   (.footer-currency)
      .global-notification   (.global-notification)
      .pager-header   (.pager-header)
      .pager-header .pages   (.pager-header)
      .pager-header .pages li   (.pager-header)
      .pager-header .controls a   (.pager-header)
      .pager-header .controls a.previous   (.pager-header)
      .pager-header .controls a.previous-disabled   (.pager-header)
      .pager-header .controls a.next   (.pager-header)
      .pager-header .controls a.next-disabled   (.pager-header)
      .message-warning .icon   (.message-warning)
      .message-notice  .icon   (.message-notice)
      img.avatar-25x25   (.avatar-25x25)
      img.avatar-30x30   (.avatar-30x30)
      .new-feature-loud   (.new-feature-loud)
      .new-feature-right   (.new-feature-right)
      .new-feature-left   (.new-feature-left)
      .identity-changes-overlay-trigger   (.identity-changes-overlay-trigger)
      .identity-changes-overlay-trigger .arrow   (.identity-changes-overlay-trigger)


    -----------------------------------------------------------------------------
    Selectors possibly unused (not found in HTML tags, but found as barewords)
     * Name that was not matched is listed in parens.
    -----------------------------------------------------------------------------

      #footer #trust   (#trust)
      .notification   (.notification)
      #listing-header   (#listing-header)
      #listing-header .total   (#listing-header)
      #listing-header .pager   (#listing-header)
      #listing-header .pages   (#listing-header)
      #listing-header .pages li   (#listing-header)
      #listing-header .controls   (#listing-header)
      #listing-header .view-type   (#listing-header)
      #listing-header .view-type a   (#listing-header)
      #listing-header .view-type span   (#listing-header)
      #sorting   (#sorting)
      #sorting.gallery   (#sorting)
      #sorting form   (#sorting)
      #sorting option   (#sorting)
      #sorting label   (#sorting)
      #sorting button   (#sorting)
      #sorting ul   (#sorting)
      .ui-humanmessage   (.ui-humanmessage)
      .ui-humanmessage .ui-widget-shadow   (.ui-humanmessage)
      .ui-humanmessage .ui-widget-content   (.ui-humanmessage)
      .usps .shipping-provider-icon   (.usps)
      .ups .shipping-provider-icon   (.ups)
      .dhl .shipping-provider-icon   (.dhl)
      .fedex .shipping-provider-icon   (.fedex)
      .unsupported .shipping-provider-icon   (.unsupported)
      .fedex input.text   (.fedex)
      .dhl input.text   (.dhl)
      .ups input.text   (.ups)
      .usps input.text   (.usps)
      .unsupported .shipping-provider-name   (.unsupported)


    -----------------------------------------------------------------------------
    Stats
    -----------------------------------------------------------------------------

      Possibly unused CSS files: 0

      Total CSS selectors: 197
      Likely unused:       21 (10.7%)
      Possibly unused:     31 (15.7%)

