<?php

/*******************************************************************************
*
*  filename    : person-list.php
*  website     : https://churchcrm.io
*  copyright   : Copyright 2019 Troy Smith
*
******************************************************************************/

use ChurchCRM\Authentication\AuthenticationManager;
use ChurchCRM\dto\SystemConfig;
use ChurchCRM\dto\SystemURLs;
use ChurchCRM\model\ChurchCRM\GroupQuery;
use ChurchCRM\model\ChurchCRM\ListOptionQuery;
use ChurchCRM\model\ChurchCRM\PersonCustomMasterQuery;
use ChurchCRM\model\ChurchCRM\PropertyQuery;


function emptyOrUnassigned($stuff){
    #This will avoid to call the db twice one to check if empty the other one to return the value
    #no caching was being done by the ORM so lets keep the value and return if not empty
    return empty($stuff) ? 'Unassigned' : $stuff;
}

function emptyOrUnassignedJSON($stuff){
    #Same as above but return json encoded
    return empty($stuff) ? 'Unassigned' : json_encode($stuff, JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
}

//Set the page title
$sPageTitle = gettext(ucfirst($sMode)) . ' ' . gettext('Listing');
include SystemURLs::getDocumentRoot() . '/Include/Header.php';
// classification list
$ListItem =  ListOptionQuery::create()->select('OptionName')->filterById(1)->find()->toArray();
$ClassificationList[] = "Unassigned";
foreach ($ListItem as $element) {
    $ClassificationList[] = $element;
}
// role list
$ListItem = ListOptionQuery::create()->select('OptionName')->filterById(2)->find()->toArray();
$RoleList[] = "Unassigned";
foreach ($ListItem as $element) {
    $RoleList[] = $element;
}
// person properties list
$ListItem = PropertyQuery::create()->filterByProClass("p")->find();
$PropertyList[] = "Unassigned";
foreach ($ListItem as $element) {
    $PropertyList[] = $element->getProName();
}

$option_name =function ($t1, $t2){
    return $t1 .":". $t2;
};

$allPersonCustomFields= PersonCustomMasterQuery::create()->find();



// person custom list
$ListItem = PersonCustomMasterQuery::create()->select(['Name', 'FieldSecurity', 'Id', 'TypeId', 'Special'])->find();

// CREATE A MAPPING FOR CUSTOMS LIKE THIS
# CustomMapping = {"c1":{"Name":"Father of confession", "Elements":{23:"option1", 24:"option2"}}, c2.... }
# allowing not only for search if has a custom set but also if is set to a given value.
$CustomMapping[] = array();

//setting unassigned to 1 so is not deleted
$CustomList["Unassigned"] = 1;

foreach ($ListItem as $element) {
    if (AuthenticationManager::getCurrentUser()->isEnabledSecurity($element["FieldSecurity"])) {
        $CustomList[$element["Name"]] = 0;
        $CustomMapping[$element["Id"]] = array("Name"=>$element["Name"], "Elements"=>array());
        if(in_array($element["TypeId"], [9, 12]) ){
            $ListElements = ListOptionQuery::create()->select(['OptionName', 'OptionId'])->filterById($element["Special"])->find()->toArray();
            foreach ($ListElements as $element2){
                $CustomList[$option_name($element["Name"], $element2["OptionName"])] = 0;
                $CustomMapping[$element["Id"]]["Elements"][$element2["OptionId"]] = $element2["OptionName"];
            }
        }
    }
}



// get person group list
$ListItem = GroupQuery::create()->find();
$GroupList[] = "Unassigned";
foreach ($ListItem as $element) {
    $GroupList[] = $element->getName();
}

?>

<div class="card card-primary">
    <div class="card-header">
        <?= gettext('Filter and Cart') ?>
    </div>

    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-6">
                <div class='external-filter'>
                <!-- <label>Gender:</label> -->
                <select style="visibility: hidden; margin: 5px; display:inline-block; width: 150px;" class="filter-Gender" multiple="multiple"></select>
                <!-- <label>Classification:</label> -->
                <select style="visibility: hidden; margin: 5px; display:inline-block; width: 150px;" class="filter-Classification" multiple="multiple"></select>
                <!-- <label>Family Role:</label> -->
                <select style="visibility: hidden; margin: 5px; display:inline-block; width: 150px;" class="filter-Role"multiple="multiple"></select>
                <!-- <label>Properties:</label> -->
                <select style="visibility: hidden; margin: 5px; display:inline-block; width: 150px;" class="filter-Properties" multiple="multiple"></select>
                <!-- <label>Custom Fields:</label> -->
                <select style="visibility: hidden; margin: 5px; display:inline-block; width: 150px;" class="filter-Custom" multiple="multiple"></select>
                <!-- <label>Group Types:</label> -->
                <select style="visibility: hidden; margin: 5px; display:inline-block; width: 150px;" class="filter-Group" multiple="multiple"></select>
                <input style="margin: 20px" id="ClearFilter" type="button" class="btn btn-default" value="<?= gettext('Clear Filter') ?>"><BR><BR>

                </div>
            </div>

            <div class= "col-lg-6">
                <a class="btn btn-success" role="button" href="<?= SystemURLs::getRootPath()?>/PersonEditor.php"><span class="fa fa-plus" aria-hidden="true"></span><?= gettext('Add Person') ?></a>
                <a id="AddAllToCart" class="btn btn-primary" ><?= gettext('Add All to Cart') ?></a>
                <!-- <input name="IntersectCart" type="submit" class="btn btn-warning" value="< ?= gettext('Intersect with Cart') ?>">&nbsp; -->
                <a id="RemoveAllFromCart" class="btn btn-danger" ><?= gettext('Remove All from Cart') ?></a>
            </div>
        </div>
    </div>

</div>
<p><br/><br/></p>
<div class="card card-warning">
    <div class="card-body">
        <table id="members" class="table table-striped table-bordered data-table" cellspacing="0" width="100%">
            <tbody>
            <!--Populate the table with person details -->
            <?php foreach ($members as $person) {
              /* @var $members ChurchCRM\people */

                ?>
            <tr>
              <td>
                    <a href='<?= SystemURLs::getRootPath()?>/PersonView.php?PersonID=<?= $person->getId() ?>'>
                        <i class="fa fa-search-plus"></i>
                    </a>
                    <a href='<?= SystemURLs::getRootPath()?>/PersonEditor.php?PersonID=<?= $person->getId() ?>'>
                            <i class="fas fa-pen"></i>
                    </a>

                    <?php if (!isset($_SESSION['aPeopleCart']) || !in_array($per_ID, $_SESSION['aPeopleCart'], false)) {
                        ?>
                            <a class="AddToPeopleCart" data-cartpersonid="<?= $person->getId() ?>">
                                <i class="fa fa-cart-plus"></i>
                            </a>
                        </td>
                        <?php
                    } else {
                        ?>
                        <a class="RemoveFromPeopleCart" data-cartpersonid="<?= $person->getId() ?>">
                                    <i class="fa fa-remove"></i>
                            </a>
                            <?php
                    }
                    ?>
                <td><?= $person->getId() ?></td>
                <td><?= $person->getLastName()?></td>
                <td><?= $person->getFirstName()?></td>
                <td><?= $person->getAddress() ?></td>
                <td><?= $person->getHomePhone() ?></td>
                <td><?= $person->getCellPhone() ?></td>
                <td><?= $person->getEmail() ?></td>
                <td><?= emptyOrUnassigned($person->getGenderName()) ?></td>
                <td><?= emptyOrUnassigned($person->getClassificationName()) ?></td>
                <td><?= emptyOrUnassigned($person->getFamilyRoleName()) ?></td>
                <td><?= emptyOrUnassignedJSON($person->getPropertiesString()) ?></td>
                <td><?= emptyOrUnassignedJSON($person->getCustomFields($allPersonCustomFields, $CustomMapping, $CustomList, $option_name)) ?></td>
                <td><?= emptyOrUnassignedJSON($person->getGroups()) ?></td>
                <?php
            }
            //lets clean all the customs that don't have anyone associated.
            foreach($CustomList as $key=>$value){
                if($value > 0) {
                    $tmp[] = $key;
                }
            }
            $CustomList=$tmp;
            

            ?>
            </tr>
            </tbody>
        </table>
    </div>
</div>

<script nonce="<?= SystemURLs::getCSPNonce() ?>" >

    var oTable;

    $(document).ready(function() {

        // setup filters
        var filterByClsId = '<?= $filterByClsId ?>';
        var filterByFmrId = '<?= $filterByFmrId ?>';
        var filterByGender = '<?= $filterByGender ?>';

        // setup datatables
        'use strict';
        var bVisible = parseInt("<?= SystemConfig::getValue('bHidePersonAddress') ? 0 : 1 ?>");

        let dataTableConfig = {
            deferRender: true,
            search: { regex: true },
            columns: [
                {
                    title:i18next.t('Actions'),
                },
                {
                    title:i18next.t('Id'),
                    visible:false
                },
                {
                    title:i18next.t('Last Name'),
                },
                {
                    title:i18next.t('First Name'),
                },
                {
                    title:i18next.t('Address'),
                    visible:bVisible
                },
                {
                    title:i18next.t('Home Phone'),
                },
                {
                    title:i18next.t('Cell Phone'),
                },
                {
                    title:i18next.t('Email'),
                },
                {
                    title:i18next.t('Gender'),
                },
                {
                    title:i18next.t('Classification'),
                },
                {
                    title:i18next.t('Roles'),
                },
                {
                    title:i18next.t('Properties'),
                    visible:false
                },
                {
                    title:i18next.t('Custom'),
                    visible:false
                },
                {
                    title:i18next.t('Group'),
                    visible:false
                }
            ],
            // sortby name
            order: [[ 2, "asc" ]]
        }

        $.extend(dataTableConfig, window.CRM.plugin.dataTable);

        oTable = $('#members').DataTable(dataTableConfig);


        $('.filter-Gender').select2({
            multiple: true,
            placeholder: i18next.t('Select') + " " + i18next.t('Gender')
         });
        $('.filter-Classification').select2({
            multiple: true,
            placeholder: i18next.t('Select') + " " + i18next.t('Classification')
        });
        $('.filter-Role').select2({
            multiple: true,
            placeholder: i18next.t('Select') + " " + i18next.t('Role')
        });
        $('.filter-Properties').select2({
            multiple: true,
            placeholder: i18next.t('Select') + " " + i18next.t('Properties')
        });
        $('.filter-Custom').select2({
            multiple: true,
            placeholder: i18next.t('Select') + " " + i18next.t('Custom')
        });
        $('.filter-Group').select2({
            multiple: true,
            placeholder: i18next.t('Select') + " " + i18next.t('Group')
        });

        $('.filter-Gender').on("change", function() {
            filterColumn(8, $(this).select2('data'), true);
        });
        $('.filter-Classification').on("change", function() {
            filterColumn(9, $(this).select2('data'), true);
        });
        $('.filter-Role').on("change", function() {
            filterColumn(10, $(this).select2('data'), true);
        });
        $('.filter-Properties').on("change", function() {
            filterColumn(11, $(this).select2('data'), false);
        });
        $('.filter-Custom').on("change", function() {
            filterColumn(12, $(this).select2('data'), false);
        });
        $('.filter-Group').on("change", function() {
            filterColumn(13, $(this).select2('data'), false);
        });

        function escapeRegExp(string) {
            return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'); // $& means the whole matched string
        }

        // apply filters
        function filterColumn(col, search, regEx) {
            if (search.length === 0) {
                tmp = [''];
            } else {
                var tmp = [];
                if (regEx) {
                    search.forEach(function(item) {
                        tmp.push('^'+escapeRegExp(item.text)+'$')});
                } else {
                    search.forEach(function(item) {
                    tmp.push('"'+escapeRegExp(item.text)+'"')});
                }
            }
            // join array into string with regex or (|)
            var val = tmp.join('|');
            // apply search
            oTable.column(col).search(val, 1, 0, 1).draw();
        }

        // the following is an example of how we can fill the gender list from the table data
        // client processing can only be done with visible columns in this case because of combined data
        // oTable.columns(8).data().eq(0).unique().sort().each( function ( d, j ) {
        //     $('.filter-Gender').append('<option>'+d+'</option>');
        // });

        // setup external DataTable filters
        var Gender = ['Male', 'Female', 'Unassigned'];
        for (var i = 0; i < Gender.length; i++) {
            if (filterByGender == Gender[i]) {
                $('.filter-Gender').val(i18next.t(Gender[i]));
                $('.filter-Gender').append('<option selected value='+i+'>'+i18next.t(Gender[i])+'</option>');
                $('.filter-Gender').trigger('change')
            } else {
            $('.filter-Gender').append('<option value='+i+'>'+i18next.t(Gender[i])+'</option>');
            }
        }
        var ClassificationList = <?= json_encode($ClassificationList, JSON_THROW_ON_ERROR) ?>;
        for (var i = 0; i < ClassificationList.length; i++) {
            // apply initinal filters if applicable
            if (filterByClsId == ClassificationList[i]) {
                $('.filter-Classification').val(ClassificationList[i]);
                $('.filter-Classification').append('<option selected value='+i+'>'+ClassificationList[i]+'</option>');
                $('.filter-Classification').trigger('change')
            } else {
               $('.filter-Classification').append('<option value='+i+'>'+ClassificationList[i]+'</option>');
            }
        }

        var RoleList = <?= json_encode($RoleList, JSON_THROW_ON_ERROR) ?>;
        for (var i = 0; i < RoleList.length; i++) {
            if (filterByFmrId == RoleList[i]) {
                $('.filter-Role').val(RoleList[i]);
                $('.filter-Role').append('<option selected value='+i+'>'+RoleList[i]+'</option>');
                $('.filter-Role').trigger('change')
            } else {
                $('.filter-Role').append('<option value='+i+'>'+RoleList[i]+'</option>');
            }
        }
        var PropertyList = <?= json_encode($PropertyList, JSON_THROW_ON_ERROR) ?>;
        for (var i = 0; i < PropertyList.length; i++) {
            $('.filter-Properties').append('<option value='+i+'>'+PropertyList[i]+'</option>');
        }
        var CustomList = <?=  json_encode($CustomList, JSON_THROW_ON_ERROR) ?>;
        for (var i = 0; i < CustomList.length; i++) {
            $('.filter-Custom').append('<option value='+i+'>'+CustomList[i]+'</option>');
        }
        var GroupList = <?= json_encode($GroupList, JSON_THROW_ON_ERROR) ?>;
        for (var i = 0; i < GroupList.length; i++) {
            $('.filter-Group').append('<option value='+i+'>'+GroupList[i]+'</option>');
        }

        // apply initinal filters if applicable
        // $('.filter-Classification').val(filterByClsId);
        // $('.filter-Classification').trigger('change')

        // clear external filters
        document.getElementById("ClearFilter").addEventListener("click", function() {

            $('.filter-Gender').val([]).trigger('change')
            $('.filter-Classification').val([]).trigger('change')
            $('.filter-Role').val([]).trigger('change')
            $('.filter-Properties').val([]).trigger('change')
            $('.filter-Custom').val([]).trigger('change')
            $('.filter-Group').val([]).trigger('change')
        });
    }); // end document ready

    $("#AddAllToCart").click(function(){
        var listPeople = [];
        var table = $('#members').DataTable().rows( { filter: 'applied' } ).every( function () {
        // fill array
        var row = this.data();
        listPeople.push(row[1]);
    });
        // bypass SelectList.js
        window.CRM.cart.addPerson(listPeople);
    });

    $("#RemoveAllFromCart").click(function(){
        var listPeople = [];
        var table = $('#members').DataTable().rows( { filter: 'applied' } ).every( function () {
        // fill array
        var row = this.data();
        listPeople.push(row[1]);
    });
        // bypass SelectList.js
        window.CRM.cart.removePerson(listPeople);
    });

</script>

<?php
require SystemURLs::getDocumentRoot() .  '/Include/Footer.php';
?>
