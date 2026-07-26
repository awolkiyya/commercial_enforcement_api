<?php
class IllegalType extends Model
{
    protected $fillable = ['name', 'severity_level', 'description'];

    public function violations()
    {
        return $this->hasMany(Violation::class);
    }
}