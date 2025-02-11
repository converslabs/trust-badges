import React, { useState, useEffect } from 'react';
import { DragDropContext, Droppable, Draggable } from 'react-beautiful-dnd';
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { useToast } from "@/components/ui/use-toast";

interface Badge {
    id: number;
    title: string;
    image_url: string;
    link_url?: string;
    position: number;
    status: 'active' | 'inactive';
}

export function BadgeManager() {
    const [badges, setBadges] = useState<Badge[]>([]);
    const [loading, setLoading] = useState(true);
    const { toast } = useToast();

    useEffect(() => {
        fetchBadges();
    }, []);

    const fetchBadges = async () => {
        try {
            const response = await fetch('/wp-json/trust-badges/v1/badges');
            const data = await response.json();
            setBadges(data);
            setLoading(false);
        } catch (error) {
            toast({
                title: "Error",
                description: "Failed to fetch badges",
                variant: "destructive",
            });
        }
    };

    const handleDragEnd = async (result: any) => {
        if (!result.destination) return;

        const items = Array.from(badges);
        const [reorderedItem] = items.splice(result.source.index, 1);
        items.splice(result.destination.index, 0, reorderedItem);

        // Update positions
        const updatedItems = items.map((item, index) => ({
            ...item,
            position: index,
        }));

        setBadges(updatedItems);

        // Update positions in database
        try {
            await fetch(`/wp-json/trust-badges/v1/badges/${reorderedItem.id}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': (window as any).wpApiSettings.nonce,
                },
                body: JSON.stringify({ position: result.destination.index }),
            });
        } catch (error) {
            toast({
                title: "Error",
                description: "Failed to update badge position",
                variant: "destructive",
            });
        }
    };

    if (loading) {
        return <div>Loading...</div>;
    }

    return (
        <div className="p-4">
            <div className="flex justify-between items-center mb-6">
                <h2 className="text-2xl font-bold">Trust Badges</h2>
                <Button>Add New Badge</Button>
            </div>

            <DragDropContext onDragEnd={handleDragEnd}>
                <Droppable droppableId="badges">
                    {(provided) => (
                        <div
                            {...provided.droppableProps}
                            ref={provided.innerRef}
                            className="space-y-4"
                        >
                            {badges.map((badge, index) => (
                                <Draggable
                                    key={badge.id}
                                    draggableId={String(badge.id)}
                                    index={index}
                                >
                                    {(provided) => (
                                        <Card
                                            ref={provided.innerRef}
                                            {...provided.draggableProps}
                                            {...provided.dragHandleProps}
                                            className="p-4"
                                        >
                                            <CardContent>
                                                <div className="flex items-center space-x-4">
                                                    <img
                                                        src={badge.image_url}
                                                        alt={badge.title}
                                                        className="w-16 h-16 object-contain"
                                                    />
                                                    <div className="flex-1">
                                                        <Label>{badge.title}</Label>
                                                        <Input
                                                            type="text"
                                                            value={badge.link_url}
                                                            placeholder="Badge link URL"
                                                            className="mt-2"
                                                        />
                                                    </div>
                                                    <div className="flex space-x-2">
                                                        <Button variant="outline">Edit</Button>
                                                        <Button variant="destructive">Delete</Button>
                                                    </div>
                                                </div>
                                            </CardContent>
                                        </Card>
                                    )}
                                </Draggable>
                            ))}
                            {provided.placeholder}
                        </div>
                    )}
                </Droppable>
            </DragDropContext>
        </div>
    );
}
