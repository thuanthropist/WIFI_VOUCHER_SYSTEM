<?php

namespace App\Livewire\Admin;

use App\Models\Payment;
use App\Models\Plan;
use App\Models\RadAcct;
use App\Models\Voucher;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class Dashboard extends Component
{
    /** Sequential blue ramp (dark steps) — one hue for the single revenue series. */
    protected string $seriesColor = '#3987e5';

    /** Categorical dark-mode steps, fixed order — used per plan, never reassigned. */
    protected array $categoryColors = ['#3987e5', '#d95926', '#199e70', '#c98500'];

    public function render()
    {
        $today = Carbon::today();

        $confirmed = Payment::query()->where('status', 'confirmed');

        $revenueToday = (clone $confirmed)->whereDate('paid_at', $today)->sum('amount');
        $revenueThisWeek = (clone $confirmed)->whereBetween('paid_at', [now()->startOfWeek(), now()->endOfWeek()])->sum('amount');
        $revenueThisMonth = (clone $confirmed)->whereMonth('paid_at', $today->month)->whereYear('paid_at', $today->year)->sum('amount');

        $dailySeries = Payment::query()
            ->selectRaw('DATE(paid_at) as day, SUM(amount) as total')
            ->where('status', 'confirmed')
            ->where('paid_at', '>=', now()->subDays(13)->startOfDay())
            ->groupBy('day')
            ->pluck('total', 'day');

        $chart = collect(range(13, 0))->map(function ($daysAgo) use ($dailySeries) {
            $date = now()->subDays($daysAgo)->toDateString();

            return [
                'label' => now()->subDays($daysAgo)->format('D'),
                'total' => (float) ($dailySeries[$date] ?? 0),
            ];
        })->values();

        $revenueByPlan = Plan::query()
            ->withSum(['payments as revenue' => function ($query) use ($today) {
                $query->where('status', 'confirmed')
                    ->whereMonth('paid_at', $today->month)
                    ->whereYear('paid_at', $today->year);
            }], 'amount')
            ->orderByDesc('revenue')
            ->take(4)
            ->get()
            ->map(fn ($plan, $i) => [
                'label' => $plan->name,
                'total' => (float) ($plan->revenue ?? 0),
                'color' => $this->categoryColors[$i % count($this->categoryColors)],
            ])
            ->values();

        $voucherStatusCounts = Voucher::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $voucherStatus = collect([
            ['key' => 'unused', 'label' => 'Unused', 'color' => '#64748b'],
            ['key' => 'active', 'label' => 'Active', 'color' => '#0ca30c'],
            ['key' => 'expired', 'label' => 'Expired', 'color' => '#d03b3b'],
        ])->map(fn ($row) => $row + ['total' => (int) ($voucherStatusCounts[$row['key']] ?? 0)]);

        return view('livewire.admin.dashboard', [
            'revenueToday' => $revenueToday,
            'revenueThisWeek' => $revenueThisWeek,
            'revenueThisMonth' => $revenueThisMonth,
            'activeSessionsCount' => RadAcct::active()->count(),
            'vouchersIssuedToday' => Voucher::whereDate('created_at', $today)->count(),
            'pendingPaymentsToday' => Payment::where('status', 'pending')->whereDate('created_at', $today)->count(),
            'activeSessions' => RadAcct::active()->latest('acctstarttime')->limit(8)->get(),
            'chart' => $chart,
            'chartMax' => max(1.0, (float) $chart->max('total')),
            'chartPath' => $this->buildSmoothAreaPath($chart->pluck('total')->all()),
            'chartLinePath' => $this->buildSmoothLinePath($chart->pluck('total')->all()),
            'seriesColor' => $this->seriesColor,
            'revenueByPlan' => $revenueByPlan,
            'revenueByPlanMax' => max(1.0, (float) $revenueByPlan->max('total')),
            'voucherStatus' => $voucherStatus,
            'voucherStatusTotal' => max(1, $voucherStatus->sum('total')),
        ]);
    }

    /**
     * Renders a smoothed line as an SVG path, using a simple Catmull-Rom to
     * cubic-bezier conversion so the trend reads as a curve rather than a
     * jagged polyline, matching the reference "Sales Overview" chart shape.
     */
    protected function buildSmoothLinePath(array $values, int $width = 700, int $height = 160): string
    {
        $points = $this->toPoints($values, $width, $height);

        return $this->pointsToSmoothPath($points);
    }

    protected function buildSmoothAreaPath(array $values, int $width = 700, int $height = 160): string
    {
        $points = $this->toPoints($values, $width, $height);
        $line = $this->pointsToSmoothPath($points);

        $first = $points[0];
        $last = $points[count($points) - 1];

        return "{$line} L {$last[0]},{$height} L {$first[0]},{$height} Z";
    }

    protected function toPoints(array $values, int $width, int $height): array
    {
        $max = max(1.0, max($values));
        $count = count($values);
        $step = $count > 1 ? $width / ($count - 1) : 0;

        return collect($values)->map(function ($value, $i) use ($max, $step, $height) {
            $x = round($i * $step, 2);
            $y = round($height - ($value / $max) * ($height - 12) - 4, 2);

            return [$x, $y];
        })->values()->all();
    }

    protected function pointsToSmoothPath(array $points): string
    {
        $path = sprintf('M %s,%s', $points[0][0], $points[0][1]);

        for ($i = 0; $i < count($points) - 1; $i++) {
            $p0 = $points[max($i - 1, 0)];
            $p1 = $points[$i];
            $p2 = $points[$i + 1];
            $p3 = $points[min($i + 2, count($points) - 1)];

            $cp1x = round($p1[0] + ($p2[0] - $p0[0]) / 6, 2);
            $cp1y = round($p1[1] + ($p2[1] - $p0[1]) / 6, 2);
            $cp2x = round($p2[0] - ($p3[0] - $p1[0]) / 6, 2);
            $cp2y = round($p2[1] - ($p3[1] - $p1[1]) / 6, 2);

            $path .= sprintf(' C %s,%s %s,%s %s,%s', $cp1x, $cp1y, $cp2x, $cp2y, $p2[0], $p2[1]);
        }

        return $path;
    }
}
