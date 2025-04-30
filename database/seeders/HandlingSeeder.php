<?php

namespace Database\Seeders;

use App\Enums\ActionEnum;
use App\Models\Employee;
use App\Models\Handling;
use App\Models\Robot;
use App\Models\Shipment;
use App\Models\ShipmentProduct;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class HandlingSeeder extends Seeder
{
    private Carbon $timer;

    public function run(): void
    {
        Shipment::factory()->has(
            ShipmentProduct::factory()->count(random_int(1, 5)), 'products'
        )->count(5)->create();

        $products = ShipmentProduct::all()->take(500000);

        $this->timer = now();
        $endTime = $this->timer->copy()->addSeconds(800);

       $robotPool = $this->getRobotPool();
       $assignedShipments = [];
       $employeePool = $this->getEmployeePool();

        while ($endTime->timestamp >= $this->timer->timestamp) {
            $timeStamp = $this->timer->timestamp;

            foreach ($robotPool as $robotId => &$robot) {
                //idle
                if ($this->timer->timestamp >= $robot['moment_idle'] && $robot['product'] === null) {
                    if ($products->isNotEmpty()) {
                        $robot['product'] = $products->pop();
                        $robot['moment_idle'] = $timeStamp + random_int(60, 180);
                        $this->createHandling(ActionEnum::FETCH_CRATE, $robot['product'], $robot['robot']);
                    }
                }

                //finished
                if ($this->timer->timestamp === $robot['moment_idle'] && $robot['product'] !== null) {
                    $this->stopHandling($robot['robot']);
                    $employeePool = $this->assignWorkloadToEmployee($assignedShipments, $robot['product'],
                                                                    $employeePool);
                    $robot['product'] = null;
                    if ($products->isNotEmpty()) {
                        $robot['product'] = $products->pop();
                        $robot['moment_idle'] = $timeStamp + random_int(60, 180);
                        $this->createHandling(ActionEnum::FETCH_CRATE, $robot['product'], $robot['robot']);
                    }
                }

                //bussy
                if ($this->timer->timestamp < $robot['moment_idle'] && $robot['product'] !== null) {
                    //dump($robotId . ' is bussy');
                }
            }
           unset($robot);

            $employeePool = $employeePool->map(function ($employee) use($timeStamp) {
                //idle
                if ($this->timer->timestamp >= $employee['moment_idle'] && $employee['product'] === null) {
                    //assign work
                    if ($employee['products']->isNotEmpty()) {
                        dump('assigning work to employee' . $employee['employee']->id);
                        $employee['moment_idle'] = $timeStamp + random_int(15, 30);
                        $employee['product'] = $employee['products']->pop();
                        $this->createHandling(ActionEnum::PICK, $employee['product'], $employee['employee']);
                    }
                }

                //finished
                if ($this->timer->timestamp === $employee['moment_idle'] && $employee['product'] !== null) {
                    //dump('employee finished') ;
                    $employee['product'] = null;
                    $this->stopHandling($employee['employee']);
                    //dump('finished' . $employee['employee_id']);
                    //assign work
                    if ($employee['products']->isNotEmpty()) {

                        dump('assigning work to employee' . $employee['employee']->id);
                        $employee['moment_idle'] = $timeStamp + random_int(15, 30);
                        $employee['product'] = $employee['products']->pop();
                        $this->createHandling(ActionEnum::PICK, $employee['product'], $employee['employee']);
                    }
                }

                //bussy
                if ($this->timer->timestamp < $employee['moment_idle']) {
                   // dump($employee['employee_id'] . ' is bussy');
                }

                return $employee;
            });

            $this->timer->addSecond();
        }

        foreach ($robotPool as $robotId => $robot) {
            $handler = $robot['robot'];
            $this->stopHandling($handler);
        }

        foreach ($employeePool as $employee) {
            $handler = $employee['employee'];
            $this->stopHandling($handler);
        }

    }

    public function createHandling(ActionEnum $action, ShipmentProduct $product, Robot|Employee $handler): Handling
    {
        $handling = new Handling();

        $handling->fill([
            'action' => $action,
            'started_at' => $this->timer,
            'handleable_type' => get_class($product),
            'handleable_id' => $product->id,
            'handler_type' => get_class($handler),
            'handler_id' => $handler->id,
        ]);
        $handling->save();

        return $handling;
    }

    public function stopHandling(Robot|Employee $handler): void
    {
        $handling = $handler->handlings()->whereNull('stopped_at')->first();

        if ($handling) {
            $handling->stopped_at = $this->timer;
            $handling->save();
        }
    }

    /**
     * @return array
     */
    public function getRobotPool(): array
    {
        $robots = Robot::all();
        if ($robots->count() < 40) {
            $create = 40 - $robots->count();
            Robot::factory()->count($create)->create();
        }

        $pool = [];
        Robot::all()->take(40)->each(function ($robot) use (&$pool) {
            $pool[$robot->id]['robot'] = $robot;
            $pool[$robot->id]['moment_idle'] = $this->timer->timestamp;
            $pool[$robot->id]['product'] = null;
        });

        return $pool;
    }

    public function getEmployeePool(): Collection
    {
        $employees = Employee::all();
        if ( $employees->count() < 4 ) {
            $create = 4 - $employees->count();
            Employee::factory()->count($create)->create();
        }

        $pool = collect();
        Employee::all()->each(function ($employee) use ($pool) {
            $handler = [
                'employee_id' => $employee->id,
                'employee' => $employee,
                'moment_idle' => $this->timer->timestamp,
                'shipments' => collect(),
                'products' => collect(),
                'product' => null,
            ];
            $pool->push($handler);
        });

        return $pool;
    }

    public function assignWorkloadToEmployee(array &$assignedShipments, ShipmentProduct $product, Collection $employeePool): Collection
    {
        if (isset($assignedShipments[$product->shipment->id])) {
            $employeeId = $assignedShipments[$product->shipment->id];

            $employeePool = $employeePool->map(function ($handler) use ($employeeId, $product) {
                if ($handler['employee_id'] === $employeeId) {
                    $handler['products']->push($product);
                }
                return $handler;
            });
        } else {
            $minWorkload = $employeePool->min(fn($handler) => count($handler['products']));

            $eligibleHandlers = $employeePool->filter(fn($handler) => count($handler['products']) === $minWorkload);
            $handler = $eligibleHandlers->random();
            $employeeId = $handler['employee_id'];

            $employeePool = $employeePool->map(function ($handler) use ($employeeId, $product) {
                if ($handler['employee_id'] === $employeeId) {
                    $handler['products']->push($product);
                }
                return $handler;
            });

            $assignedShipments[$product->shipment->id] = $employeeId;
        }

        return $employeePool;
    }
}
