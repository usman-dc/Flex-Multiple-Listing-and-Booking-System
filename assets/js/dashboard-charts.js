/**
 * Admin dashboard Chart.js initialization.
 *
 * @package FlexBookingSystem
 */
( function () {
	'use strict';

	if ( typeof window.Chart === 'undefined' || ! window.fbsDashboardCharts ) {
		return;
	}

	var cfg = window.fbsDashboardCharts;

	document.addEventListener( 'DOMContentLoaded', function () {
		var mainCtx = document.getElementById( 'fbs-chart-main' );
		if ( mainCtx ) {
			new window.Chart( mainCtx, {
				type: 'line',
				data: {
					labels: cfg.labels,
					datasets: [
						{
							label: cfg.bookingsLabel,
							data: cfg.bookings,
							borderColor: '#0d6efd',
							backgroundColor: 'rgba(13,110,253,0.1)',
							fill: true,
							tension: 0.3,
							yAxisID: 'y',
						},
						{
							label: cfg.revenueLabel,
							data: cfg.revenue,
							borderColor: '#198754',
							backgroundColor: 'rgba(25,135,84,0.08)',
							fill: true,
							tension: 0.3,
							yAxisID: 'y1',
						},
					],
				},
				options: {
					responsive: true,
					interaction: { mode: 'index', intersect: false },
					plugins: { legend: { position: 'top' } },
					scales: {
						y: {
							beginAtZero: true,
							position: 'left',
							title: { display: true, text: cfg.bookingsLabel },
						},
						y1: {
							beginAtZero: true,
							position: 'right',
							grid: { drawOnChartArea: false },
							title: { display: true, text: cfg.currency },
						},
					},
				},
			} );
		}

		var statusCtx = document.getElementById( 'fbs-chart-status' );
		if ( statusCtx ) {
			new window.Chart( statusCtx, {
				type: 'doughnut',
				data: {
					labels: cfg.statusLabels,
					datasets: [
						{
							data: cfg.statusCounts,
							backgroundColor: cfg.statusColors,
						},
					],
				},
				options: {
					responsive: true,
					plugins: { legend: { position: 'bottom', labels: { boxWidth: 12 } } },
				},
			} );
		}

		var typesCtx = document.getElementById( 'fbs-chart-types' );
		if ( typesCtx ) {
			new window.Chart( typesCtx, {
				type: 'bar',
				data: {
					labels: cfg.typeLabels,
					datasets: [
						{
							label: cfg.bookingsLabel,
							data: cfg.typeCounts,
							backgroundColor: '#0d6efd',
						},
					],
				},
				options: {
					responsive: true,
					plugins: { legend: { display: false } },
					scales: { y: { beginAtZero: true } },
				},
			} );
		}
	} );
} )();
