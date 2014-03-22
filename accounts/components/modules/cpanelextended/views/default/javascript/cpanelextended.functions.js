$(function() 
{
    var LOADERURL = '/blesta/components/modules/cpanelextended/views/default/images/loader.gif';
    var BASEURL = document.location.href;
    
    $.fn.showLoaderInsideTable = function(content)
    {
        var element = this.closest('.modifyData');

        if(element.length)
            return element.show().find('td').html('<div style="margin: 5px 10px;"><img src="'+ LOADERURL +'"><span style="margin-left: 10px;">' + content + '</span></div>');
        else
            return this.parent().parent().next('.modifyData').show().find('td').html('<div style="margin: 5px 10px;"><img src="'+ LOADERURL +'"><span style="margin-left: 10px;">' + content + '</span></div>'); 
    };
    
    $.fn.refreshTableData = function()
    {
        var that = this;
        
        that.html('<tr><td colspan="5"><div style="margin: 5px 10px;"><img src="'+ LOADERURL +'"><span style="margin-left: 10px;">Please wait, we are fetching data</span></div></td></tr>');
        
        $.get(BASEURL, function(result)
        {
            that.html($('table.table tbody', result).html());
        });
    }
    
    $('.common_nav ul li a.ajax').bind('click', function()
    {
       $('.inner').html('<div style="margin: 10px 20px;"><img src="'+ LOADERURL +'"> Please wait while loading...</div>'); 
    });
    
    $('.form').delegate('#createFtpAccount', 'click', function(e)
    {
        var that = $(this);
        
        console.log(that.serialize());
        
        e.preventDefault(); 
    });
    
    if($('#successmsg').length) {
        $('html, body').animate({
            scrollTop: $('#successmsg').offset().top
        }, 1500);
    }

    //$('.deleteFtp').blestaModal();
    
    $('.deleteRedir').live('click', function(e)
    {
        var that = $(this);
        
        that.parents().closest('form').attr('action', that.attr('data-action'));
    });
    
    $('.deleteuserfromdb').live('click', function(e)
    {
        var that = $(this);
        
        that.parents().closest('form').attr('action', that.attr('data-action'));
    });
    
    $('.changeQuotaSubmit, .deleteFtpSubmit, .cpanelExtendedAjaxExecute').live('submit', function(e) 
    {
        var that = $(this);
        var data = that.serialize();
        
        var action = that.attr('data-do');
        var url = that.attr('action');
        var clone = that.clone();
        var box = that.showLoaderInsideTable(LOADERTEXT);
        //BASEURL + action
        
        $.post(url, data, function(response)
        {
            if(response.success === true)
            {
                location.reload(); // May it should be ajax loaded, may be... :)
            }
            else
            { 
                box.html(clone);
                
                var result = '<div class="message_box"><ul style="padding: 0;">';
                
                if(typeof response.data != 'undefined')
                {
                    for(var i=0;i<response.data.length;i++)
                    {
                      result += '<li class="error"><a href="#" class="cross_btn">×</a>';
                      result += '<p>'+ response.data[i].message +'</p>';
                      result += '</li>';
                    }
                }
                else
                {
                    result += '<li class="error"><p>Unknown error occured</p></li>';
                }
                
                result += '</ul></div>';

                var errorsbox = $('<div></div>').addClass('errorscontainer').prependTo(box).html(result);
                
                $('html, body').animate({
                    scrollTop: errorsbox.offset().top
                }, 2000);

                //location.reload();
                //alert(response.data[0].message);
            }
        });

        e.preventDefault();
    });
    
    $('.changeFtpQuota, .deleteFtp, .cpanelExtendedAjaxRequest').live('click', function(e) {
        /*$(this).blestaModal(
        {
            url  : $(this).attr('href'),
            text : 'Please wait while loading...',
            open : true,
            min_width: 600,
            min_height: 400
        });*/
        var that = $(this);
        
        that.parent().showLoaderInsideTable('Please wait while loading...');

        $.get(that.attr('href'), function(result)
        {
            var row = that.parent().parent().parent().next('.modifyData');   
            row.find('td').html(result);
            
            $('html, body').animate({
                scrollTop: row.offset().top
            }, 1000);
        });
 
        e.preventDefault();
    });
    
    $('.generatePassword').live('click', function(e)
    {
        var that = $(this);
        var field1 = that.attr('data-field1');
        var field2 = that.attr('data-field2');
        var length = 8,
            charset = "abcdefghijklnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789",
            retVal = "";
            
        for (var i = 0, n = charset.length; i < length; ++i) {
            retVal += charset.charAt(Math.floor(Math.random() * n));
        }

        $('#'+ field1 + ',#' + field2).val(retVal);
        
        if(that.attr('data-ajax') == 1)
        {
            that.parent().parent().parent().next('tr').next('tr').show().find('td.generatedAjaxPassword').text(retVal);
        }
        else
        {
            $('#generatedPassword').show().find('strong').text(retVal);
        }    

        e.preventDefault();
    });
    
    $('.hideFormBox').live('click', function(e)
    {
       var that = $(this);
       
       that.closest('.modifyData').hide('medium');
       
       e.preventDefault();
    });
    
    $('#showUploadKeyForm').bind('click', function(e)
    {
        $('#uploadSslKeyBox').show();
        $('#generateSslKeyBox').hide();
        
        e.preventDefault();
    });
    
    $('#showGenerateKeyForm').bind('click', function(e)
    {
        $('#uploadSslKeyBox').hide();
        $('#generateSslKeyBox').show();
        
        e.preventDefault();
    });
    
    $('#showUploadCrtForm').bind('click', function(e)
    {
        $('#uploadCrtBox').show();
        $('#generateCrtBox').hide();
        
        e.preventDefault();
    });
    
    $('#showGenerateCrtForm').bind('click', function(e)
    {
        $('#uploadCrtBox').hide();
        $('#generateCrtBox').show();
        
        e.preventDefault();
    });
    
    $('#showGenerateCsrForm').bind('click', function(e)
    {
        $('#generateCsrBox').show();
        e.preventDefault(); 
    });
    
    $('#ftpUsername').bind('keyup', function(e)
    {
       var that = $(this);
       
       $('#ftpDirectory').val(that.val());
    });
    
    $('#addondomainname').bind('keyup', function(e)
    {
       var that = $(this);
       var prefix = 'public_html/';

       $('#addondomainuser').val(that.val().replace(/[^a-z0-9]/gi, ''));
       $('#addondomainroot').val(prefix + that.val());
    });
    
    $('[name="subdomainname"]').bind('blur', function(e)
    {
       var that = $(this);
       
       $('[name="subdomainroot"]').val('public_html/' + that.val());
    });
    
    $('#togglePrivileges').bind('click', function(e)
    {
        var that = $(this);

        if(that.find('input').is(':checked'))
        {
            that.parent().find('.privileges:not(:checked)').trigger('click');
        }
        else
        {
            that.parent().find('.privileges:checked').trigger('click');
        }
    });
    
    $('#cronSettingsAjax').live('change', function()
    {
       var that = $(this);
       var settings = that.val().split(' ');

       for(var i=0;i<settings.length;i++)
       {
           that.parent().parent().find('td:eq('+ i +') input').val(settings[i]);
       }
    });
    
    $('[id^="cronCommonSetting"]').bind('change', function() 
    {
        var that = $(this);
        var value = that.val();
        var id = that.attr('id');
        
        if(id == 'cronCommonSetting')
        {
            var parts = value.split(' ');
            
            $('[name="jobminute"]').val(parts[0]);
            $('[name="jobhour"]').val(parts[1]);
            $('[name="jobday"]').val(parts[2]);
            $('[name="jobmonth"]').val(parts[3]);
            $('[name="jobweekday"]').val(parts[4]);
        }
        else
        {
            that.parent().find('input').val(value);
        }
    });
    
    $('textarea.selectAll').live('click', function() 
    {
       var that = $(this);
       
       that.select();
    });
    
    $.validator.setDefaults({
       errorElement : 'span',
       submitHandler : function(form)
       {
           var form    = $(form);
           var action  = form.attr('action');
           var data    = form.serialize();
           var btnText = form.find('button:last').text();
           
           form.find('button:last').attr('disabled', 'disabled').addClass('disable').text('Please wait...');
           
           $.post(action, data, function(response)
           {
              if(response)
              {
                  if(response.success)
                  {
                      location.reload();
                  }
                  else
                  {
                      var result = '<div class="message_box" style="margin-top: 20px;"><ul style="padding: 0;">';
                      
                      for(var i=0;i<response.data.length;i++)
                      {
                          result += '<li class="error"><a href="#" class="cross_btn">×</a>';
                          result += '<p>'+ response.data[i].message +'</p>';
                          result += '</li>';
                      }
                      
                      result += '</ul></div>';
                      
                      form.parent().next('.errorscontainer').html(result);
                      
                      form.find('button:last').removeAttr('disabled').removeClass('disable').text(btnText);
                  }
              }
              else
              {
                  alert('Unknown error occured...');
              }
           });

           return false;
       }
    });
    
    jQuery.validator.addMethod("domain", function(name)
    {
            /*
            name = nname.replace('http://','');
            nname = nname.replace('https://','');

            var arr = new Array(
            '.com','.net','.org','.biz','.coop','.info','.museum','.name',
            '.pro','.edu','.gov','.int','.mil','.ac','.ad','.ae','.af','.ag',
            '.ai','.al','.am','.an','.ao','.aq','.ar','.as','.at','.au','.aw',
            '.az','.ba','.bb','.bd','.be','.bf','.bg','.bh','.bi','.bj','.bm',
            '.bn','.bo','.br','.bs','.bt','.bv','.bw','.by','.bz','.ca','.cc',
            '.cd','.cf','.cg','.ch','.ci','.ck','.cl','.cm','.cn','.co','.cr',
            '.cu','.cv','.cx','.cy','.cz','.de','.dj','.dk','.dm','.do','.dz',
            '.ec','.ee','.eg','.eh','.er','.es','.et','.fi','.fj','.fk','.fm',
            '.fo','.fr','.ga','.gd','.ge','.gf','.gg','.gh','.gi','.gl','.gm',
            '.gn','.gp','.gq','.gr','.gs','.gt','.gu','.gv','.gy','.hk','.hm',
            '.hn','.hr','.ht','.hu','.id','.ie','.il','.im','.in','.io','.iq',
            '.ir','.is','.it','.je','.jm','.jo','.jp','.ke','.kg','.kh','.ki',
            '.km','.kn','.kp','.kr','.kw','.ky','.kz','.la','.lb','.lc','.li',
            '.lk','.lr','.ls','.lt','.lu','.lv','.ly','.ma','.mc','.md','.mg',
            '.mh','.mk','.ml','.mm','.mn','.mo','.mp','.mq','.mr','.ms','.mt',
            '.mu','.mv','.mw','.mx','.my','.mz','.na','.nc','.ne','.nf','.ng',
            '.ni','.nl','.no','.np','.nr','.nu','.nz','.om','.pa','.pe','.pf',
            '.pg','.ph','.pk','.pl','.pm','.pn','.pr','.ps','.pt','.pw','.py',
            '.qa','.re','.ro','.rw','.ru','.sa','.sb','.sc','.sd','.se','.sg',
            '.sh','.si','.sj','.sk','.sl','.sm','.sn','.so','.sr','.st','.sv',
            '.sy','.sz','.tc','.td','.tf','.tg','.th','.tj','.tk','.tm','.tn',
            '.to','.tp','.tr','.tt','.tv','.tw','.tz','.ua','.ug','.uk','.um',
            '.us','.uy','.uz','.va','.vc','.ve','.vg','.vi','.vn','.vu','.ws',
            '.wf','.ye','.yt','.yu','.za','.zm','.zw');

            var mai = nname;
            var val = true;

            var dot = mai.lastIndexOf(".");
            var dname = mai.substring(0,dot);
            var ext = mai.substring(dot,mai.length);
            //alert(ext);

            if(dot>2 && dot<57)
            {
                    for(var i=0; i<arr.length; i++)
                    {
                      if(ext == arr[i])
                      {
                            val = true;
                            break;
                      }     
                      else
                      {
                            val = false;
                      }
                    }
                    if(val == false)
                    {
                             return false;
                    }
                    else
                    {
                            for(var j=0; j<dname.length; j++)
                            {
                              var dh = dname.charAt(j);
                              var hh = dh.charCodeAt(0);
                              if((hh > 47 && hh<59) || (hh > 64 && hh<91) || (hh > 96 && hh<123) || hh==45 || hh==46)
                              {
                                     if((j==0 || j==dname.length-1) && hh == 45)    
                                     {
                                              return false;
                                     }
                              }
                            else    {
                                     return false;
                              }
                            }
                    }
            }
            else
            {
             return false;
            }
            return true;*/
            
            var expression = new RegExp(/(.*?)[^w{3}\.]([a-zA-Z0-9]([a-zA-Z0-9\-]{0,65}[a-zA-Z0-9])?\.)+[a-zA-Z]{2,6}/igm);
            
            return expression.test(name);
    }, 'Invalid domain format.');
    
    $.validator.addMethod("alphanumeric", function(value, element) {
            return this.optional(element) || /^[a-zA-Z0-9]+$/.test(value);
    }, 'This field must be alphanumeric');
    
    $('#createFtpAccount').validate({
        rules: {
            ftpusername: {
                required: true,
                alphanumeric : true,
                minlength: 2
            },
            ftppassword: {
                required: true,
                minlength: 5
            },
            ftppasswordconfirm: {
                required: true,
                minlength: 5,
                equalTo: '[name="ftppassword"]'
            },
       }
    });
    
    $('#addUserToDb').validate({
       rules : {
           database : {
               required : true
           },
           dbuser : {
               required : true
           }
       } 
    });
    
    $('#createMysqlDatabase').validate({
       rules : {
           dbname : {
               required : true,
               alphanumeric : true,
           }
       } 
    });
    
    $('#createMysqlUser').validate({
       rules : {
           dbusername : {
               required : true,
               alphanumeric : true,
           },
           dbpassword : {
               required : true
           },
           dbpasswordconfirm : {
               required : true,
               equalTo : '[name="dbpassword"]'
           }
       } 
    });
    
    $('#createParkedDomain').validate({
       rules : {
           domainname : {
               required : true,
               domain : true,
           }
       } 
    });
    
    $('#createAddonDomain').validate({
       rules : {
           newdomain : {
               required : true,
               domain : true,
           },
           domainusername : {
               required : true
           },
           documentroot : {
               required : true
           },
           domainpassword : {
               required : true
           }
       } 
    });
    
    $('#createSubdomain').validate({
       rules : {
           subdomainname : {
               required : true,
           },
           subdomainroot : {
               required : true
           }
       } 
    });
    
    $('#createCronJob').validate({
       rules : {
           jobminute : {
               required : true
           },
           jobhour : {
               required : true
           },
           jobday : {
               required : true
           },
           jobmonth : {
               required : true
           },
           jobweekday : {
               required : true
           },
           command : {
               required : true
           }
       } 
    });
    
    $('#createEmailAccount').validate({
       rules: {
            emailusername: {
                required: true,
                minlength: 2
            },
            emailpassword: {
                required: true,
                minlength: 5
            },
            emailpasswordconfirm: {
                required: true,
                minlength: 5,
                equalTo: '[name="emailpassword"]'
            }
       } 
    });
    
    $('#generateCrt').validate({
       rules : {
           domain : {
               required: true,
               domain : true
           }
       } 
    });
    
    $('#generateCsr').validate({
       rules : {
           domain : {
               required: true,
               domain : true
           }
       } 
    });
    
    $('#generateSslKey').validate({
       rules : {
           keysize : {
               required: true
           }
       } 
    });
    
    $('#uploadKey').validate({
       rules : {
           key : {
               required : true
           }
       } 
    });
    
    $('#uploadCrt').validate({
       rules : {
           key : {
               required : true
           }
       } 
    });
});