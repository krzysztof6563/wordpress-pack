<?php
//contact form redirect
add_action("wpcf7_before_send_mail", "redirectCF7Form");

private function getMailMapping($department) {
    #if (stristr($_SERVER['HTTP_HOST'], 'dev.example.com') !== false) {
     #   return $this->devMailing[$department] ?? false;
   # } else {
        return $this->prodMailing[$department] ?? false;
    #}
}

function redirectCF7Form() {
    /** @var \WPCF7_ContactForm $wpcf */
      $wpcf = WPCF7_ContactForm::get_current();
      if ($wpcf->id == XXXXX) {
          $submission = WPCF7_Submission::get_instance();  
          if ($submission) {
              $posted_data = $submission->get_posted_data();
              $topic = $posted_data['contact-subject'][0];
              
              if ($topic) {
                  $mail = $wpcf->prop( 'mail' );
                  $newRecipent = $this->getMailMapping($topic);
                  
                  if ($newRecipent !== false) {
                      $mail['recipient'] = $newRecipent;
                      $wpcf->set_properties(['mail' => $mail]);        
                  }
              }
          }
      }

      return $wpcf;
  }


private $devMailing = [
    "Test" => 'test@example.com',
];

private $prodMailing = [
    "Department XX" => "department@example.com",
];
