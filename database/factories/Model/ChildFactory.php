<?php

namespace Database\Factories\Model;

use App\Enums\GuardianRelation;
use App\Model\Child;
use App\Model\Group;
use App\Model\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ChildFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Child::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'group_id' => Group::factory(),
            'notification' => true,
            'auto_checkIn' => false,
        ];
    }

    /**
     * Kind mit Bezugsperson; Rechte folgen den Standardrechten der Beziehungsart.
     */
    public function withGuardian(User $user, GuardianRelation $relation = GuardianRelation::LegalGuardian, array $pivot = []): static
    {
        return $this->afterCreating(function (Child $child) use ($user, $relation, $pivot) {
            $child->parents()->attach($user->id, $pivot + ['relation' => $relation->value] + $relation->defaultRights());
        });
    }

    public function inClass(Group $class): static
    {
        return $this->state(fn () => ['class_id' => $class->id]);
    }

    /**
     * Indicate that the child has notifications disabled.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function withoutNotification()
    {
        return $this->state(function (array $attributes) {
            return [
                'notification' => false,
            ];
        });
    }

    /**
     * Indicate that the child has auto check-in enabled.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function withAutoCheckIn()
    {
        return $this->state(function (array $attributes) {
            return [
                'auto_checkIn' => true,
            ];
        });
    }
}
