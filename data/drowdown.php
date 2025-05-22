<?php
namespace PPC_CRM;

defined( 'ABSPATH' ) || exit;

return [
    'days'            => [ 'Mon','Tue','Wed','Thu','Fri','Sat','Sun' ],
    'client_types'    => [ 'Existing Client','New Client' ],
    'occasions'       => [ 'Anniversary','Birthday','Casual','Engagement/Wedding','Gifting','N/A' ],
    'attempt_types'   => [ 'Connected:Not Relevant','Connected:Relevant','Not Connected' ],
    'attempt_statuses'=> [ 'Call Rescheduled','Just Browsing','Not Interested','Ringing','No Response','Store Visit Scheduled','Wrong/Invalid Number' ],
    'months'          => [ 'Jan', 'Feb', /*…*/ 'Dec' ],
];
