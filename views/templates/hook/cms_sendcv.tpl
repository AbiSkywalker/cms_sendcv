<!-- CMS Custom HTML -->

<section class="cms_send_cv mt-3">
  <form action="" method="post" enctype="multipart/form-data" >

    {if isset($notifications.nw_error) || isset($notifications.messages)}
      <div class="col-xs-8 offset-xs-2 text-xs-center alert {if $notifications.nw_error}alert-danger{else}alert-success{/if}">
        {foreach $notifications.messages as $notif}
          <p>{$notif}</p>
        {/foreach}
      </div>
    {/if}
      <section class="form-fields">

        <div class="form-group row">
          <label class="col-md-4 form-control-label">{l s='Full Name' d='Modules.Cmssendcv.Shop'} <sup>*</sup></label>
          <div class="col-md-6">
            <input
              class="form-control"
              name="fullname"
              type="text"
              value="{$contact.firstname} {$contact.lastname}"
              placeholder="John Doe"
            >
          </div>
        </div>

        <div class="form-group row">
          <label class="col-md-4 form-control-label">{l s='Phone' d='Modules.Cmssendcv.Shop'} <sup>*</sup></label>
          <div class="col-md-6">
            <input
              class="form-control"
              name="phone"
              type="text"
              value="{$contact.phone}"
              placeholder="{l s='Phone' d='Modules.Cmssendcv.Shop'}"
            >
          </div>
        </div>

        <div class="form-group row">
          <label class="col-md-4 form-control-label">{l s='E-mail' d='Modules.Cmssendcv.Shop'} <sup>*</sup></label>
          <div class="col-md-6">
            <input
              class="form-control"
              name="email"
              type="email"
              value="{$contact.email}"
              placeholder="{l s='your@email.com' d='Shop.Forms.Help'}"
            >
          </div>
        </div>
            
        <div class="form-group row">
          <label class="col-md-4 form-control-label">{l s='Location' d='Modules.Cmssendcv.Shop'} <sup>*</sup></label>
          <div class="col-md-6">
            <input
              class="form-control"
              name="location"
              type="text"
              value="{$contact.address}"
              placeholder="{l s='Location' d='Modules.Cmssendcv.Shop'}"
            >
          </div>
        </div>
        
        <div class="form-group row">
          <label class="col-md-4 form-control-label">{l s='Desired job position' d='Modules.Cmssendcv.Shop'} <sup>*</sup></label>
          <div class="col-md-6">
            {assign var="job_positions_options" value=";"|explode:$job_positions}
            <select class="form-control" name="position" id="position">
                {foreach from=$job_positions_options item=jobposition}
                    {if $jobposition != ''}
                        <option value="{$jobposition|trim}">{$jobposition|trim}</option>
                    {/if}
                {/foreach}
            </select>
          </div>
          <div class="col-md-3"></div>
        </div>

        <div class="form-group row">
            <label  class="col-md-4 form-control-label" for="fileUpload">{l s='Your CV file'} <sup>*</sup></label>
            <div class="col-md-6">
                <input type="hidden" name="MAX_FILE_SIZE" value="2000000" />
                <input type="file" class="required form-control" id="cvFileUpload" name="cvFileUpload" />
            </div>
        </div>
                
        
        {if isset($id_module)}
          <div class="form-group row">
            <div class="offset-md-4 col-md-6">
              {hook h='displayGDPRConsent' id_module=$id_module}
            </div>
          </div>
        {/if}

      </section>

      <footer class="form-footer text-sm-center">
        <input type="hidden" name="tipo" value="{if $cms.id == "13"}normal{else}negativa{/if}" />
        <input type="hidden" name="token" value="{$token}" />
        <input class="btn btn-primary" type="submit" name="submitMessage" value="{l s='Send' d='Shop.Theme.Actions'}">
      </footer>

  </form>
</section>

<!-- /CMS Custom HTML -->