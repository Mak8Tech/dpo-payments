import React, { useState, useEffect } from "react";
import {
  AlertCircle,
  Check,
  X,
  Loader2,
  CreditCard,
  Smartphone,
} from "lucide-react";

export const DpoSubscriptionSignup = ({
  plans = [],
  defaultCountry = "ZM",
  onSuccess,
  onError,
  apiEndpoint = "/api/dpo/subscriptions",
}) => {
  const [selectedPlan, setSelectedPlan] = useState(null);
  const [formData, setFormData] = useState({
    customer_email: "",
    customer_name: "",
    customer_phone: "",
    country: defaultCountry,
    frequency: "monthly",
    start_date: new Date().toISOString().split("T")[0],
  });

  const [processing, setProcessing] = useState(false);
  const [error, setError] = useState("");

  const handleSubmit = async (e) => {
    e.preventDefault();

    if (!selectedPlan) {
      setError("Please select a subscription plan");
      return;
    }

    setProcessing(true);
    setError("");

    try {
      const response = await fetch(apiEndpoint, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          ...formData,
          amount: selectedPlan.amount,
          currency: selectedPlan.currency,
          description: selectedPlan.name,
        }),
      });

      const data = await response.json();

      if (data.success) {
        if (onSuccess) {
          onSuccess(data);
        }
      } else {
        throw new Error(data.error || "Subscription creation failed");
      }
    } catch (err) {
      setError(err.message);
      if (onError) onError(err);
    } finally {
      setProcessing(false);
    }
  };

  const handleChange = (field, value) => {
    setFormData((prev) => ({ ...prev, [field]: value }));
  };

  return (
    <div className="max-w-2xl mx-auto p-6">
      <h2 className="text-3xl font-bold text-gray-900 mb-8">
        Choose Your Subscription
      </h2>

      {error && (
        <div className="mb-4 p-3 bg-red-50 border border-red-200 rounded-md flex items-center">
          <AlertCircle className="h-5 w-5 text-red-600 mr-2" />
          <span className="text-red-800">{error}</span>
        </div>
      )}

      {/* Plan Selection */}
      <div className="grid md:grid-cols-3 gap-4 mb-8">
        {plans.map((plan) => (
          <div
            key={plan.id}
            onClick={() => setSelectedPlan(plan)}
            className={`p-6 border-2 rounded-lg cursor-pointer transition-all ${
              selectedPlan?.id === plan.id
                ? "border-indigo-600 bg-indigo-50"
                : "border-gray-200 hover:border-gray-300"
            }`}
          >
            <h3 className="text-lg font-semibold mb-2">{plan.name}</h3>
            <p className="text-3xl font-bold mb-2">
              {plan.currency} {plan.amount}
              <span className="text-sm font-normal text-gray-600">/month</span>
            </p>
            <ul className="space-y-2 text-sm text-gray-600">
              {plan.features?.map((feature, idx) => (
                <li key={idx} className="flex items-start">
                  <Check className="h-4 w-4 text-green-500 mr-2 mt-0.5" />
                  <span>{feature}</span>
                </li>
              ))}
            </ul>
          </div>
        ))}
      </div>

      {/* Subscription Form */}
      <form
        onSubmit={handleSubmit}
        className="bg-white rounded-lg shadow-lg p-6"
      >
        <h3 className="text-xl font-semibold mb-4">Billing Information</h3>

        <div className="grid md:grid-cols-2 gap-4">
          {/* Email */}
          <div className="mb-4">
            <label className="block text-sm font-medium text-gray-700 mb-1">
              Email Address
            </label>
            <input
              type="email"
              required
              value={formData.customer_email}
              onChange={(e) => handleChange("customer_email", e.target.value)}
              className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
            />
          </div>

          {/* Name */}
          <div className="mb-4">
            <label className="block text-sm font-medium text-gray-700 mb-1">
              Full Name
            </label>
            <input
              type="text"
              required
              value={formData.customer_name}
              onChange={(e) => handleChange("customer_name", e.target.value)}
              className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
            />
          </div>

          {/* Phone */}
          <div className="mb-4">
            <label className="block text-sm font-medium text-gray-700 mb-1">
              Phone Number
            </label>
            <input
              type="tel"
              value={formData.customer_phone}
              onChange={(e) => handleChange("customer_phone", e.target.value)}
              className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
            />
          </div>

          {/* Start Date */}
          <div className="mb-4">
            <label className="block text-sm font-medium text-gray-700 mb-1">
              Start Date
            </label>
            <input
              type="date"
              required
              min={new Date().toISOString().split("T")[0]}
              value={formData.start_date}
              onChange={(e) => handleChange("start_date", e.target.value)}
              className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
            />
          </div>

          {/* Frequency */}
          <div className="mb-4 md:col-span-2">
            <label className="block text-sm font-medium text-gray-700 mb-1">
              Billing Frequency
            </label>
            <select
              value={formData.frequency}
              onChange={(e) => handleChange("frequency", e.target.value)}
              className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
            >
              <option value="monthly">Monthly</option>
              <option value="quarterly">Quarterly</option>
              <option value="yearly">Yearly</option>
            </select>
          </div>
        </div>

        <div className="mt-6">
          <button
            type="submit"
            disabled={processing || !selectedPlan}
            className="w-full py-3 px-4 bg-indigo-600 text-white font-semibold rounded-md hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors flex items-center justify-center"
          >
            {processing ? (
              <>
                <Loader2 className="animate-spin h-5 w-5 mr-2" />
                Processing...
              </>
            ) : (
              "Start Subscription"
            )}
          </button>
        </div>
      </form>
    </div>
  );
};
