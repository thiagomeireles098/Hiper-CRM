"use client";
import { useEffect } from "react";
import { useRouter } from "next/navigation";
import { getWorkspaceStatus, isLoggedIn } from "./auth";

export function useAuthGuard() {
  const r = useRouter();
  useEffect(() => {
    if (!isLoggedIn()) r.replace("/login");
  }, [r]);
}

export function useActiveSubscriptionGuard() {
  const r = useRouter();
  useEffect(() => {
    if (!isLoggedIn()) return;
    const st = getWorkspaceStatus();
    if (st !== "ACTIVE") r.replace("/assinatura");
  }, [r]);
}