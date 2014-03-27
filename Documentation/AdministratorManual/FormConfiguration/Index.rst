.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../Includes.txt

.. _admin-manual-form-configuration:

Form Configuration
------------------



.. container:: table-row

   Property
         showCalendarMonth

   Data type
         integer

   Description
		In the list of offers a calendar may be shown to allow customers
		selecting a different date near the first selected checkin date.

		The default is only to show 2 to 3 weeks around this date.

		But you can show a whole month view too by setting this option.

   Default
         0: don't show month view


.. container:: table-row

   Property
         showCalendarWeek

   Data type
         integer

   Description
		In the list of offers a 2 to 3 weeks around the selected checkin
		date are shown.

		The number of weeks after the week with the selected checkin date.

   Default
         1: Show one week after the checkin date.


[tsref:plugin.tx_abbooking_pi1.form]
