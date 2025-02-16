import React, { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { Button } from '@/components/ui/button'
import { Card } from '@/components/ui/card'
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { toast } from '@/components/ui/use-toast'
import { PlusCircle, Trash2, Edit2 } from 'lucide-react'

interface Badge {
  id: number
  title: string
  image_url: string
  link_url: string
  position: number
  status: 'active' | 'inactive'
}

async function fetchBadges(): Promise<Badge[]> {
  const response = await fetch(`${window.txBadgesSettings.restUrl}/badges`, {
    headers: {
      'X-WP-Nonce': window.txBadgesSettings.nonce,
      'Content-Type': 'application/json'
    }
  });
  if (!response.ok) throw new Error('Failed to fetch badges');
  return response.json();
}

async function createBadge(badge: Omit<Badge, 'id'>): Promise<Badge> {
  const response = await fetch(`${window.txBadgesSettings.restUrl}/badges`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-WP-Nonce': window.txBadgesSettings.nonce
    },
    body: JSON.stringify(badge)
  });
  if (!response.ok) throw new Error('Failed to create badge');
  return response.json();
}

function BadgeList() {
  const [isDialogOpen, setIsDialogOpen] = useState(false)
  const [newBadge, setNewBadge] = useState({
    title: '',
    image_url: '',
    link_url: '',
    position: 0,
    status: 'active' as const
  })

  const queryClient = useQueryClient()

  const { data: badges = [], isLoading, error } = useQuery({
    queryKey: ['badges'],
    queryFn: fetchBadges
  })

  const createMutation = useMutation({
    mutationFn: createBadge,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['badges'] })
      setIsDialogOpen(false)
      setNewBadge({
        title: '',
        image_url: '',
        link_url: '',
        position: 0,
        status: 'active'
      })
      toast({
        title: 'Success',
        description: 'Badge created successfully'
      })
    }
  })

  if (isLoading) return <div>Loading...</div>
  if (error) return <div>Error: {(error as Error).message}</div>

  return (
    <div>
      <div className="mb-6 flex justify-between items-center">
        <h2 className="text-2xl font-semibold">Manage Badges</h2>
        <Dialog open={isDialogOpen} onOpenChange={setIsDialogOpen}>
          <DialogTrigger asChild>
            <Button>
              <PlusCircle className="mr-2 h-4 w-4" />
              Add New Badge
            </Button>
          </DialogTrigger>
          <DialogContent>
            <DialogHeader>
              <DialogTitle>Add New Badge</DialogTitle>
            </DialogHeader>
            <div className="grid gap-4 py-4">
              <div className="grid gap-2">
                <Label htmlFor="title">Title</Label>
                <Input
                  id="title"
                  value={newBadge.title}
                  onChange={(e) => setNewBadge({ ...newBadge, title: e.target.value })}
                />
              </div>
              <div className="grid gap-2">
                <Label htmlFor="image_url">Image URL</Label>
                <Input
                  id="image_url"
                  value={newBadge.image_url}
                  onChange={(e) => setNewBadge({ ...newBadge, image_url: e.target.value })}
                />
              </div>
              <div className="grid gap-2">
                <Label htmlFor="link_url">Link URL</Label>
                <Input
                  id="link_url"
                  value={newBadge.link_url}
                  onChange={(e) => setNewBadge({ ...newBadge, link_url: e.target.value })}
                />
              </div>
              <Button onClick={() => createMutation.mutate(newBadge)}>
                Create Badge
              </Button>
            </div>
          </DialogContent>
        </Dialog>
      </div>

      <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
        {badges.map((badge) => (
          <Card key={badge.id} className="p-4">
            <div className="aspect-square relative mb-4">
              <img
                src={badge.image_url}
                alt={badge.title}
                className="w-full h-full object-contain"
              />
            </div>
            <div className="space-y-2">
              <h3 className="font-medium">{badge.title}</h3>
              <p className="text-sm text-muted-foreground">
                {badge.link_url}
              </p>
              <div className="flex gap-2">
                <Button variant="outline" size="sm">
                  <Edit2 className="h-4 w-4" />
                </Button>
                <Button variant="destructive" size="sm">
                  <Trash2 className="h-4 w-4" />
                </Button>
              </div>
            </div>
          </Card>
        ))}
      </div>
    </div>
  )
}

export default BadgeList
