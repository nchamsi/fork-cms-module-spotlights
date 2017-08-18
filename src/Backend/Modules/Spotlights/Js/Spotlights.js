/*
 * This file is part of Fork CMS.
 *
 * For the full copyright and license information, please view the license
 * file that was distributed with this source code.
 */

/**
 * Interaction for the Spotlights module
 *
 * @author Wouter Verstuyf <info@webflow.be>
 */
jsBackend.spotlights =
{
    // constructor
    init: function()
    {
        // index stuff
        if($('.jsDataGridSpotlightsHolder').length > 0)
        {
            // destroy default drag and drop
            $('.sequenceByDragAndDrop tbody').sortable('destroy');

            // drag and drop
            jsBackend.spotlights.bindDragAndDropSpotlights();
            jsBackend.spotlights.checkForEmptyCategories();
        }

        // handle links
        jsBackend.spotlights.toggleLinks();

        // do meta
        if($('#title').length > 0) $('#title').doMeta();
    },

    toggleLinks: function()
    {
        // checkbox checked?
        if(!$('#externalLink').is(':checked'))
        {
            $('#externalLinks').toggle();

            $('#externalLink').click(function(){
                $('#externalLinks').toggle();
                $('#internalLinks').toggle();
            });
        }
        // nope
        else
        {
            $('#internalLinks').toggle();

            $('#externalLink').click(function(){
                $('#externalLinks').toggle();
                $('#internalLinks').toggle();
            });
        }
    },

    /**
     * Check for empty categories and make it still possible to drop spotlights
     */
    checkForEmptyCategories: function()
    {
        // reset initial empty grids
        $('table.emptyGrid').each(function(){
            $(this).find('td').parent().remove();
            $(this).append('<tr class="noItems"><td colspan="' + $(this).find('th').length + '">' + jsBackend.locale.msg('NoItems') +'</td></tr>');
            $(this).removeClass('emptyGrid');
        });

        // when there are empty categories
        if($('tr.noItems').length > 0)
        {
            // make dataGrid droppable
            $('table.jsDataGrid').droppable(
            {
                // only accept table rows
                accept: 'table.jsDataGrid tr',
                drop: function(e, ui)
                {
                    // remove the no items in category message
                    $(this).find('tr.noItems').remove();
                }
            });

            // cleanup remaining no items
            $('table.jsDataGrid').each(function(){
                if($(this).find('tr').length > 2) $(this).find('tr.noItems').remove();
            });
        }
    },

    /**
     * Bind drag and dropping of a category
     */
    bindDragAndDropSpotlights: function()
    {
        // go over every dataGrid
        $.each($('div.jsDataGridSpotlightsHolder'), function()
        {
            // make them sortable
            $('div.jsDataGridSpotlightsHolder').sortable(
            {
                items: 'table.jsDataGrid tbody tr',        // set the elements that user can sort
                handle: 'td.dragAndDropHandle',            // set the element that user can grab
                tolerance: 'pointer',                    // give a more natural feeling
                connectWith: 'div.jsDataGridSpotlightsHolder',        // this is what makes dragging between categories possible
                stop: function(e, ui)                // on stop sorting
                {
                    // vars we will need
                    var spotlightId = ui.item.attr('id');
                    var fromCategoryId = $(this).attr('id').substring(9);
                    var toCategoryId = ui.item.parents('.jsDataGridSpotlightsHolder').attr('id').substring(9);
                    var fromCategorySequence = $(this).sortable('toArray').join(',');
                    var toCategorySequence = $('#dataGrid-' + toCategoryId).sortable('toArray').join(',');

                    // make ajax call
                    $.ajax(
                    {
                        data:
                        {
                            fork: { action: 'Sequence' },
                            spotlightId: spotlightId,
                            fromCategoryId: fromCategoryId,
                            toCategoryId: toCategoryId,
                            fromCategorySequence: fromCategorySequence,
                            toCategorySequence: toCategorySequence
                        },
                        success: function(data, textStatus)
                        {
                            // successfully saved reordering sequence
                            if(data.code == 200)
                            {
                                // change count in title (if any)
                                $('div#dataGrid-' + fromCategoryId + ' .content-title p').html($('div#dataGrid-' + fromCategoryId + ' .content-title p').html().replace(/\(([0-9]*)\)$/, '(' + ( $('div#dataGrid-' + fromCategoryId + ' table.jsDataGrid tr').length - 1 ) + ')'));

                                // if there are no records -> show message
                                if($('div#dataGrid-' + fromCategoryId + ' table.jsDataGrid tr').length == 1)
                                {
                                    $('div#dataGrid-' + fromCategoryId + ' table.jsDataGrid').append('<tr class="noItems"><td colspan="3">' + jsBackend.locale.msg('NoItems') + '</td></tr>');
                                }

                                // check empty categories
                                jsBackend.spotlights.checkForEmptyCategories();

                                // redo odd-even
                                var table = $('table.jsDataGrid');
                                table.find('tr').removeClass('odd').removeClass('even');
                                table.find('tr:even').addClass('even');
                                table.find('tr:odd').addClass('odd');

                                // change count in title (if any)
                                $('div#dataGrid-' + toCategoryId + ' .content-title p').html($('div#dataGrid-' + toCategoryId + ' .content-title p').html().replace(/\(([0-9]*)\)$/, '(' + ( $('div#dataGrid-' + toCategoryId + ' table.jsDataGrid tr').length - 1 ) + ')'));

                                // show message
                                jsBackend.messages.add('success', data.message);
                            }
                            // not a success so revert the changes
                            else
                            {
                                // revert
                                $(this).sortable('cancel');

                                // show message
                                jsBackend.messages.add('danger', 'alter sequence failed.');
                            }

                            // alert the user
                            if(data.code != 200 && jsBackend.debug){ alert(data.message); }
                        },
                        error: function(XMLHttpRequest, textStatus, errorThrown)
                        {
                            // revert
                            $(this).sortable('cancel');

                            // show message
                            jsBackend.messages.add('danger', 'alter sequence failed.');

                            // alert the user
                            if(jsBackend.debug){ alert(textStatus); }
                        }
                    });
                }
            });
        });
    }
}

$(jsBackend.spotlights.init);
