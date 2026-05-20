"use client";
import Sidebar from "@/components/Sidebar";
import { useAuthGuard, useActiveSubscriptionGuard } from "@/lib/guards";

export default function AppLayout({ children }: { children: React.ReactNode }) {
  useAuthGuard();
  useActiveSubscriptionGuard();

  return (
    <div className="min-h-screen flex">
      <Sidebar />
      <main className="flex-1 p-4 md:p-6">{children}</main>
    </div>
  );
}