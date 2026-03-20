define(['jquery'], function ($) {
    'use strict';

    /**
     * Populate a <select> element with an array of {value, label} objects.
     * Keeps a placeholder as the first option.
     *
     * @param {jQuery} $select
     * @param {Array}  items
     * @param {string} placeholder
     */
    function populateSelect($select, items, placeholder) {
        $select.empty().append(
            $('<option>', { value: '', text: placeholder, disabled: true, selected: true })
        );
        $.each(items, function (i, item) {
            $select.append($('<option>', { value: item.value, text: item.label }));
        });
    }

    /**
     * Show AJAX loading state on a select element.
     *
     * @param {jQuery} $select
     * @param {boolean} loading
     */
    function setLoading($select, loading) {
        if (loading) {
            $select.addClass('fitment-loading').prop('disabled', true);
        } else {
            $select.removeClass('fitment-loading');
        }
    }

    /**
     * Build a query string from a plain object.
     *
     * @param {Object} params
     * @returns {string}
     */
    function buildQuery(params) {
        return $.param(params);
    }

    /* ------------------------------------------------------------------ */
    /*  initForm                                                            */
    /* ------------------------------------------------------------------ */

    function initForm(config) {
        var makesUrl  = config.makesUrl;
        var modelsUrl = config.modelsUrl;
        var yearsUrl  = config.yearsUrl;
        var searchUrl = config.searchUrl;
        var clearUrl  = config.clearUrl;

        var $make      = $('#fitment-make');
        var $model     = $('#fitment-model');
        var $year      = $('#fitment-year');
        var $searchBtn = $('#fitment-search-btn');
        var $clearBtn  = $('#fitment-clear-btn');

        // ---- Load makes on DOM ready ------------------------------------ //
        $.ajax({
            url: makesUrl,
            type: 'GET',
            dataType: 'json',
            beforeSend: function () {
                setLoading($make, true);
            },
            success: function (data) {
                populateSelect($make, data, '-- เลือกยี่ห้อ --');
            },
            error: function (xhr, status, err) {
                console.error('FitmentSearch: ไม่สามารถโหลดยี่ห้อรถได้', err);
                alert('เกิดข้อผิดพลาด: ไม่สามารถโหลดข้อมูลยี่ห้อรถได้ กรุณาลองใหม่อีกครั้ง');
            },
            complete: function () {
                setLoading($make, false);
            }
        });

        // ---- Make change → load models ---------------------------------- //
        $make.on('change', function () {
            var make = $(this).val();

            // Reset downstream selects
            $model.prop('disabled', true).empty().append(
                $('<option>', { value: '', text: '-- เลือกรุ่นรถ --', disabled: true, selected: true })
            );
            $year.prop('disabled', true).empty().append(
                $('<option>', { value: '', text: '-- เลือกปี --', disabled: true, selected: true })
            );
            $searchBtn.prop('disabled', true);

            if (!make) { return; }

            $.ajax({
                url: modelsUrl + '?' + buildQuery({ make: make }),
                type: 'GET',
                dataType: 'json',
                beforeSend: function () {
                    setLoading($model, true);
                },
                success: function (data) {
                    populateSelect($model, data, '-- เลือกรุ่นรถ --');
                    $model.prop('disabled', false);
                },
                error: function (xhr, status, err) {
                    console.error('FitmentSearch: ไม่สามารถโหลดรุ่นรถได้', err);
                    alert('เกิดข้อผิดพลาด: ไม่สามารถโหลดข้อมูลรุ่นรถได้ กรุณาลองใหม่อีกครั้ง');
                },
                complete: function () {
                    setLoading($model, false);
                }
            });
        });

        // ---- Model change → load years ---------------------------------- //
        $model.on('change', function () {
            var make  = $make.val();
            var model = $(this).val();

            // Reset year
            $year.prop('disabled', true).empty().append(
                $('<option>', { value: '', text: '-- เลือกปี --', disabled: true, selected: true })
            );
            $searchBtn.prop('disabled', true);

            if (!make || !model) { return; }

            $.ajax({
                url: yearsUrl + '?' + buildQuery({ make: make, model: model }),
                type: 'GET',
                dataType: 'json',
                beforeSend: function () {
                    setLoading($year, true);
                },
                success: function (data) {
                    populateSelect($year, data, '-- เลือกปี --');
                    $year.prop('disabled', false);
                },
                error: function (xhr, status, err) {
                    console.error('FitmentSearch: ไม่สามารถโหลดข้อมูลปีรถได้', err);
                    alert('เกิดข้อผิดพลาด: ไม่สามารถโหลดข้อมูลปีรถได้ กรุณาลองใหม่อีกครั้ง');
                },
                complete: function () {
                    setLoading($year, false);
                }
            });
        });

        // ---- Year change → enable search button ------------------------- //
        $year.on('change', function () {
            var year = $(this).val();
            $searchBtn.prop('disabled', !year);
        });

        // ---- Search button click ---------------------------------------- //
        $searchBtn.on('click', function (e) {
            e.preventDefault();
            var make  = $make.val();
            var model = $model.val();
            var year  = $year.val();

            if (!make || !model || !year) {
                alert('กรุณาเลือกยี่ห้อ รุ่น และปีรถก่อนค้นหา');
                return;
            }

            window.location.href = searchUrl + '?' + buildQuery({ make: make, model: model, year: year });
        });

        // ---- Clear button click ----------------------------------------- //
        if ($clearBtn.length) {
            $clearBtn.on('click', function (e) {
                e.preventDefault();
                $.ajax({
                    url: clearUrl,
                    type: 'POST',
                    dataType: 'json',
                    success: function () {
                        window.location.reload();
                    },
                    error: function (xhr, status, err) {
                        console.error('FitmentSearch: ไม่สามารถล้างข้อมูลรถได้', err);
                        alert('เกิดข้อผิดพลาด: ไม่สามารถล้างข้อมูลรถได้ กรุณาลองใหม่อีกครั้ง');
                    }
                });
            });
        }
    }

    /* ------------------------------------------------------------------ */
    /*  initWidget                                                          */
    /* ------------------------------------------------------------------ */

    function initWidget(config) {
        var makesUrl  = config.makesUrl;
        var modelsUrl = config.modelsUrl;
        var yearsUrl  = config.yearsUrl;
        var searchUrl = config.searchUrl;
        var clearUrl  = config.clearUrl;

        var $make      = $('#fitment-widget-make');
        var $model     = $('#fitment-widget-model');
        var $year      = $('#fitment-widget-year');
        var $searchBtn = $('#fitment-widget-search-btn');
        var $clearBtn  = $('#fitment-widget-clear');

        // ---- Load makes on init ----------------------------------------- //
        $.ajax({
            url: makesUrl,
            type: 'GET',
            dataType: 'json',
            beforeSend: function () {
                setLoading($make, true);
            },
            success: function (data) {
                populateSelect($make, data, '-- ยี่ห้อ --');
            },
            error: function (xhr, status, err) {
                console.error('FitmentSearch Widget: ไม่สามารถโหลดยี่ห้อรถได้', err);
                alert('เกิดข้อผิดพลาด: ไม่สามารถโหลดข้อมูลยี่ห้อรถได้ กรุณาลองใหม่อีกครั้ง');
            },
            complete: function () {
                setLoading($make, false);
            }
        });

        // ---- Make change → load models ---------------------------------- //
        $make.on('change', function () {
            var make = $(this).val();

            $model.prop('disabled', true).empty().append(
                $('<option>', { value: '', text: '-- รุ่น --', disabled: true, selected: true })
            );
            $year.prop('disabled', true).empty().append(
                $('<option>', { value: '', text: '-- ปี --', disabled: true, selected: true })
            );
            $searchBtn.prop('disabled', true);

            if (!make) { return; }

            $.ajax({
                url: modelsUrl + '?' + buildQuery({ make: make }),
                type: 'GET',
                dataType: 'json',
                beforeSend: function () {
                    setLoading($model, true);
                },
                success: function (data) {
                    populateSelect($model, data, '-- รุ่น --');
                    $model.prop('disabled', false);
                },
                error: function (xhr, status, err) {
                    console.error('FitmentSearch Widget: ไม่สามารถโหลดรุ่นรถได้', err);
                    alert('เกิดข้อผิดพลาด: ไม่สามารถโหลดข้อมูลรุ่นรถได้ กรุณาลองใหม่อีกครั้ง');
                },
                complete: function () {
                    setLoading($model, false);
                }
            });
        });

        // ---- Model change → load years ---------------------------------- //
        $model.on('change', function () {
            var make  = $make.val();
            var model = $(this).val();

            $year.prop('disabled', true).empty().append(
                $('<option>', { value: '', text: '-- ปี --', disabled: true, selected: true })
            );
            $searchBtn.prop('disabled', true);

            if (!make || !model) { return; }

            $.ajax({
                url: yearsUrl + '?' + buildQuery({ make: make, model: model }),
                type: 'GET',
                dataType: 'json',
                beforeSend: function () {
                    setLoading($year, true);
                },
                success: function (data) {
                    populateSelect($year, data, '-- ปี --');
                    $year.prop('disabled', false);
                },
                error: function (xhr, status, err) {
                    console.error('FitmentSearch Widget: ไม่สามารถโหลดข้อมูลปีรถได้', err);
                    alert('เกิดข้อผิดพลาด: ไม่สามารถโหลดข้อมูลปีรถได้ กรุณาลองใหม่อีกครั้ง');
                },
                complete: function () {
                    setLoading($year, false);
                }
            });
        });

        // ---- Year change → enable search button ------------------------- //
        $year.on('change', function () {
            var year = $(this).val();
            $searchBtn.prop('disabled', !year);
        });

        // ---- Search button click ---------------------------------------- //
        $searchBtn.on('click', function (e) {
            e.preventDefault();
            var make  = $make.val();
            var model = $model.val();
            var year  = $year.val();

            if (!make || !model || !year) {
                alert('กรุณาเลือกยี่ห้อ รุ่น และปีรถก่อนค้นหา');
                return;
            }

            window.location.href = searchUrl + '?' + buildQuery({ make: make, model: model, year: year });
        });

        // ---- Widget clear pill click ------------------------------------ //
        if ($clearBtn.length) {
            $clearBtn.on('click', function (e) {
                e.preventDefault();
                $.ajax({
                    url: clearUrl,
                    type: 'POST',
                    dataType: 'json',
                    success: function () {
                        window.location.reload();
                    },
                    error: function (xhr, status, err) {
                        console.error('FitmentSearch Widget: ไม่สามารถล้างข้อมูลรถได้', err);
                        alert('เกิดข้อผิดพลาด: ไม่สามารถล้างข้อมูลรถได้ กรุณาลองใหม่อีกครั้ง');
                    }
                });
            });
        }
    }

    return {
        initForm:   initForm,
        initWidget: initWidget
    };
});
