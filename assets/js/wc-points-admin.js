(function ($) {
    var currentUser;
    $(document).ready(function () {
        $('.wc-points-loading').hide();
        $('.wc-points-user').click(function () {
            currentUser = $(this).attr('data-user');
            $('#wc-operation-user').val(currentUser);
            get_user_data();
        });
        var currentPage, limit, pages;
        $('.wc-points-navigation').click(function (e) {
            e.preventDefault();
            if ($(this).hasClass('prev-page')) {
                currentPage--;
                currentPage = currentPage < 1 ? 1 : currentPage;
            } else {
                currentPage++;
                currentPage = currentPage > pages ? pages : currentPage;
            }
            get_user_data(limit, currentPage);
        });
        function get_user_data(limit, page) {
            $('.wc-points-loading').show();
            $('.wc-points-content').hide();
            var data = {
                user_id: currentUser,
                extract_limit: limit || 10,
                extract_page: page || 1,
                action: 'get_user_data'
            };
            $.post(wc_points_admin.adminUrl, data).done(function (data) {
                try {
                    data = JSON.parse(data);
                } catch (e) {
                    alert('Error');
                    return;
                }
                console.log(data);
                var target = $('#wc-points-extract');
                var tbody = target.find('tbody');
                tbody.empty();
                $.each(data.data.extract.data, function (i, v) {
                    var line = $('<tr>');
                    $('<td>').text(v.description).appendTo(line);
                    $('<td>').text(v.entryFormated).appendTo(line);
                    $('<td>').text(v.expiredFormated).appendTo(line);
                    $('<td>').text(v.pointsFormated).appendTo(line);
                    $('<td>').text(v.current_points).appendTo(line);
                    line.appendTo(tbody);
                });
                var totalPages = Math.ceil(data.data.extract.total / data.data.limit);
                target.find('.displaying-num').html(data.data.page + ' - ' + totalPages);
                currentPage = data.data.page;
                limit = data.data.limit;
                pages = totalPages;
                $('#wc-points-user-current-points').html(data.data.currentPointsFormated + ' PTS');
                $('#wc-points-user-factor-conversion').html(data.data.conversionFactor);
                $('.wc-points-loading').hide();
                $('.wc-points-content').show();
            });
        }
        $('#wc-points-tabs').tabs();
        $('.wc-points-form').submit(function (e) {
            e.preventDefault();
            $('.wc-points-loading').show();
            $('.wc-points-content').hide();
            $.post($(this).attr('action'), $(this).serialize()).done(function (data) {
                var message;
                try {
                    data = JSON.parse(data);
                    message = data.message;
                } catch(e) {
                    message = data;
                }
                console.log(data);
                get_user_data();
            });
            $(this).get(0).reset();
            
        });
    });
})(jQuery);