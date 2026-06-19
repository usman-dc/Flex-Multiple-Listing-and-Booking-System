/**
 * Admin dashboard Chart.js initialization.
 *
 * @package FlexBookingSystem
 */
( function () {
	'use strict';

	if ( typeof window.Chart === 'undefined' || ! window.ulbmDashboardCharts ) {
		return;
	}

	var cfg = window.ulbmDashboardCharts;

	function sparkOptions( color, fill ) {
		return {
			type: 'line',
			data: {
				labels: cfg.sparkLabels || [],
				datasets: [
					{
						data: [],
						borderColor: color,
						backgroundColor: fill,
						borderWidth: 2,
						fill: true,
						tension: 0.4,
						pointRadius: 0,
						pointHitRadius: 0,
					},
				],
			},
			options: {
				responsive: true,
				maintainAspectRatio: false,
				plugins: { legend: { display: false }, tooltip: { enabled: false } },
				scales: {
					x: { display: false },
					y: { display: false },
				},
				elements: { line: { borderCapStyle: 'round' } },
			},
		};
	}

	function initSpark( id, dataKey, color, fill ) {
		var el = document.getElementById( id );
		if ( ! el || ! cfg[ dataKey ] ) {
			return;
		}
		var wrap = el.parentElement;
		if ( wrap ) {
			wrap.style.height = '40px';
		}
		var options = sparkOptions( color, fill );
		options.data.datasets[ 0 ].data = cfg[ dataKey ];
		new window.Chart( el, options );
	}

	document.addEventListener( 'DOMContentLoaded', function () {
		var mainCtx = document.getElementById( 'ulbm-chart-main' );
		if ( mainCtx ) {
			new window.Chart( mainCtx, {
				data: {
					labels: cfg.labels,
					datasets: [
						{
							type: 'bar',
							label: cfg.bookingsLabel,
							data: cfg.bookings,
							backgroundColor: 'rgba(99, 102, 241, 0.22)',
							borderColor: 'rgba(99, 102, 241, 0.35)',
							borderWidth: 1,
							borderRadius: 4,
							yAxisID: 'y',
						},
						{
							type: 'line',
							label: cfg.bookingsLabel,
							data: cfg.bookings,
							borderColor: '#4f46e5',
							backgroundColor: 'transparent',
							borderWidth: 2.5,
							tension: 0.35,
							pointRadius: 3,
							pointBackgroundColor: '#4f46e5',
							pointBorderColor: '#fff',
							pointBorderWidth: 2,
							yAxisID: 'y',
						},
					],
				},
				options: {
					responsive: true,
					maintainAspectRatio: false,
					interaction: { mode: 'index', intersect: false },
					plugins: {
						legend: { display: false },
					},
					scales: {
						x: {
							grid: { display: false },
							ticks: { maxTicksLimit: 8, font: { size: 10 } },
						},
						y: {
							beginAtZero: true,
							grid: { color: 'rgba(148, 163, 184, 0.2)' },
							ticks: { font: { size: 10 } },
						},
					},
				},
			} );
		}

		var statusCtx = document.getElementById( 'ulbm-chart-status' );
		if ( statusCtx ) {
			new window.Chart( statusCtx, {
				type: 'doughnut',
				data: {
					labels: cfg.statusLabels,
					datasets: [
						{
							data: cfg.statusCounts,
							backgroundColor: cfg.statusColors,
							borderWidth: 0,
						},
					],
				},
				options: {
					responsive: true,
					maintainAspectRatio: true,
					cutout: '68%',
					plugins: { legend: { display: false } },
				},
			} );
		}

		initSpark( 'ulbm-spark-bookings', 'sparkBookings', '#6366f1', 'rgba(99, 102, 241, 0.15)' );
		initSpark( 'ulbm-spark-revenue', 'sparkRevenue', '#ffffff', 'rgba(255, 255, 255, 0.12)' );
		initSpark( 'ulbm-spark-customers', 'sparkBookings', '#6366f1', 'rgba(99, 102, 241, 0.12)' );
		initSpark( 'ulbm-spark-types', 'sparkBookings', '#6366f1', 'rgba(99, 102, 241, 0.12)' );
	} );
} )();
